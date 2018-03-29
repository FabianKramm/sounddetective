<?php
namespace SoundDetective\Update;

use Memcached;
use Exception;
use SoundDetective\Util\CustomStream;
use SoundDetective\Util\DatabaseStream;
use SoundDetective\Util\HttpRequest;
use SoundDetective\Util\Log;
use SoundDetective\Util\Util;

/**
 * Class SpotifyUpdateCrawler
 * @package SoundDetective\Update
 */
class SpotifyUpdateCrawler{
    private static $file = "tmp/artists";

    /** @var Memcached */
    private $memcached;

    /** @var Log */
    private $log;

    /** @var SoundDetectiveUpdateDB */
    private $db;

    /**
     * Config
     */
    const amountRelatedUpdate = 40;
    const amountTopSongsUpdate = 40;
    const amountTracksUpdate = 1000;
    const amountLastFMImageUpdate = 10;

    const minArtistPopularity = 25;
    const minSongPopularity = 20;

    const updateArtistAfter = 345600; // Last 4 Days
    const updateSongAfter = 345600; // Last 4 Days

    const updateTrackAfter = 604800;
    const updateRelatedAfter = 1004800;

    const updateArtistTopSongsAfter = 804800;

    const lastFMKey = Config::last_fm_key;
    const lastFMUrl = "http://ws.audioscrobbler.com/2.0/";

    /**
     * SpotifyUpdateCrawler constructor.
     */
    function __construct()
    {
        $this->memcached = new Memcached;
        $this->memcached->addServer('localhost', 11211) or die ("Could not connect to Memcached");

        $this->log = Log::getInstance();
        $this->db = SoundDetectiveUpdateDB::getInstance();
    }

    private function isTokenTimeout()
    {
        if( $this->memcached->get('updateSpotifyToken') ){
            return false;
        }
        else{
            return true;
        }
    }

    private function getBearerToken()
    {
        if( $this->isTokenTimeout() ){
            $r = json_decode(HttpRequest::sendPostRequest("https://accounts.spotify.com/api/token", array("grant_type" => "client_credentials"), array("Authorization: Basic " . Config::spotify_token)), true);

            if( $r["access_token"] && $r["expires_in"] ){
                $this->memcached->set('updateSpotifyToken', $r["access_token"], time() + 3599);
            }
            else{
                throw new Exception("Strange token answer!", 1);
            }
        }

        return $this->memcached->get('updateSpotifyToken');
    }

    public function getSpotifyRequest($url)
    {
        $url = "https://api.spotify.com/v1/" . $url;

        $this->log->write("Get url: " . $url);

        $r = json_decode(HttpRequest::sendGetRequest($url, array("Authorization: Bearer " . $this->getBearerToken())), true);

        if( $r && isset($r["error"]) && isset($r["error"]["status"]) && $r["error"]["status"] == 429 ){
            $this->log->write("Sleeping because of rate limit!!");

            sleep(2);

            return $this->getSpotifyRequest(substr($url, 27));
        }

        return $r;
    }

    private function createNewArtist($spotifyId, $name, $image_background = NULL, $popularity = -1)
    {
        $this->db->insert(Config::table_update_artists, [
            "spotify_id" => $spotifyId,
            "name" => $name,
            "image_background" => $image_background,
            "popularity" => $popularity,
            "created" => time(),
            "last_update" => time()
        ]);

        return $this->db->getInsertId();
    }

    private function isRemix($songTitle)
    {
        if( strpos(strtolower($songTitle), "remix") !== FALSE ){
            return 1;
        }

        return 0;
    }

    private function isAcoustic($songTitle)
    {
        if( strpos(strtolower($songTitle), "acoustic") !== FALSE || strpos(strtolower($songTitle), "accoustic") !== FALSE ){
            return 1;
        }

        return 0;
    }

    private function createNewSong($songTitle, $songArtists)
    {
        // Format Title
        $songTitle = Util::formatTitleSimple($songTitle);

        // Check if song already exists
        $this->db->where ("title = ? AND artists = ?", [$songTitle, $songArtists]);
        $dbSong = $this->db->getOne(Config::table_update_songs);

        if( $dbSong ){
            return [
                "id" => $dbSong["id"],
                "is_remix" => $this->isRemix($songTitle),
                "is_acoustic" => $this->isAcoustic($songTitle)
            ];
        }
        else{
            $this->db->insert(Config::table_update_songs, [
                "title" => $songTitle,
                "artists" => $songArtists,
                "created" => time(),
                "last_update" => time()
            ]);

            return [
                "id" => $this->db->getInsertId(),
                "is_remix" => $this->isRemix($songTitle),
                "is_acoustic" => $this->isAcoustic($songTitle)
            ];
        }
    }

    private function calculateCrossedPopularity($song_popularity, $artist_popularity)
    {
        $song_popularity = intval($song_popularity);
        $artist_popularity = intval($artist_popularity);

        $difference = $song_popularity - $artist_popularity;
        $returnValue = $song_popularity;

        if( $difference >= 0 ){
            $returnValue += min(6, round($difference * 1.4)) + 2;
        }

        return ($artist_popularity == -1) ? $song_popularity : max(0, min(100, $returnValue));
    }

    /**
     * @param $track array
     * @param $release_date int
     * @param $stream DatabaseStream With Format: "spotify_id", "song_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","is_remix","is_acoustic","duration","last_update","release_date","created"
     */
    private function createTrack($track, &$stream, $release_date = NULL)
    {
        $artistArr = [NULL, NULL, NULL];
        $artistPop = -1;

        $artistsNames = [];

        for($j=0;$j < count($track["artists"]) && $j < 3;$j++){
            $this->db->where ("spotify_id = ?", [$track["artists"][$j]["id"]]);
            $artist = $this->db->getOne(Config::table_update_artists);

            if( $artist ){
                $artistArr[$j] = $artist["id"];

                if( $j == 0 ){
                    $artistPop = $artist["popularity"];
                }
            }
            else{
                $artistArr[$j] = $this->createNewArtist($track["artists"][$j]["id"], $track["artists"][$j]["name"]);

                if( $j == 0 ){
                    $artistPop = -1;
                }
            }

            $artistsNames[] = $track["artists"][$j]["name"];
        }

        // exclude lame songs
        if (isset($track["popularity"]) && $track["popularity"] < self::minSongPopularity) {
            return;
        }

        // Insert Song
        $songArr = $this->createNewSong($track["name"], implode(";;",$artistsNames));

        $song_id = $songArr["id"];
        $isRemix = $songArr["is_remix"];
        $isAcoustic = $songArr["is_acoustic"];

        // iSRC
        $isrc = ( isset($track["external_ids"]) && isset($track["external_ids"]["isrc"]) ) ? $track["external_ids"]["isrc"] : NULL;

        // duration
        $duration = ( isset($track["duration_ms"]) ) ? $track["duration_ms"] : NULL;

        if ( !$release_date ){
            $this->db->where ("spotify_id = ?", [$track["album"]["id"]]);
            $dbAlbum = $this->db->getOne(Config::table_update_albums);

            $release_date = $dbAlbum["release_date"];
        }

        $stream->add([$track["id"], $song_id, $isrc, $track["popularity"], $this->calculateCrossedPopularity($track["popularity"], $artistPop), $artistArr[0], $artistPop, $artistArr[1], $artistArr[2], $isRemix, $isAcoustic, $duration, time(), $release_date, time()]);
    }

    private function updateTrack($track, $dbTrack, &$stream)
    {
        $artistArr = [NULL, NULL, NULL];
        $artistPop = -1;

        $artistsNames = [];

        for($j=0;$j < count($track["artists"]) && $j < 3;$j++){
            $this->db->where ("spotify_id = ?", [$track["artists"][$j]["id"]]);
            $artist = $this->db->getOne(Config::table_update_artists);

            if( $artist ){
                $artistArr[$j] = $artist["id"];

                if( $j == 0 ){
                    $artistPop = $artist["popularity"];
                }
            }
            else{
                $artistArr[$j] = $this->createNewArtist($track["artists"][$j]["id"], $track["artists"][$j]["name"]);

                if( $j == 0 ){
                    $artistPop = -1;
                }
            }

            $artistsNames[] = $track["artists"][$j]["name"];
        }

        // Song Id
        $song_id = $dbTrack["song_id"];
        $isRemix = $dbTrack["is_remix"];
        $isAcoustic = $dbTrack["is_acoustic"];

        if( intval($dbTrack["song_id"]) == -1 ){
            // Insert Song
            $songArr = $this->createNewSong($track["name"], implode(";;",$artistsNames));

            $song_id = $songArr["id"];
            $isRemix = $songArr["is_remix"];
            $isAcoustic = $songArr["is_acoustic"];
        }

        // iSRC
        $isrc = ( isset($track["external_ids"]) && isset($track["external_ids"]["isrc"]) ) ? $track["external_ids"]["isrc"] : NULL;

        // duration
        $duration = ( isset($track["duration_ms"]) ) ? $track["duration_ms"] : NULL;

        $stream->add([$dbTrack["id"], $song_id, $isrc, $track["popularity"], $this->calculateCrossedPopularity($track["popularity"], $artistPop), $artistArr[0], $artistPop, $artistArr[1], $artistArr[2], $isRemix, $isAcoustic, $duration, time()]);
    }

    /*
     * Gets Albums from Spotify and inserts them into the Database
     */
    private function getAlbums($ids)
    {
        $url = "albums/?ids=" . implode(",", $ids);
        $insertAlbums = new DatabaseStream(Config::table_update_albums, ["spotify_id", "release_date"]);

        $r = $this->getSpotifyRequest($url);

        if( $r["albums"] ){
            for($j=0;$j<count($r["albums"]);$j++){
                if( $r["albums"][$j]["release_date"] ){
                    $splitted = explode("-", $r["albums"][$j]["release_date"]);

                    if( count($splitted) == 1 ){
                        $release_date = mktime(0, 0, 0, 6, 12, intval($splitted[0]));
                    }
                    else if ( count($splitted) == 2 ){
                        $release_date = mktime(0, 0, 0, intval($splitted[1]), 12, intval($splitted[0]));
                    }
                    else if ( count($splitted) == 3 ){
                        $release_date = mktime(0, 0, 0, intval($splitted[1]), intval($splitted[2]), intval($splitted[0]));
                    }
                    else{
                        throw new Exception("No release date in json " . json_encode($r));
                    }

                    $insertAlbums->add([$r["albums"][$j]["id"], $release_date]);
                }
                else{
                    throw new Exception("No release date in json " . json_encode($r));
                }
            }

            $insertAlbums->commit();
        }
    }

    /**
     * Gets Tracks from Spotify and inserts them into an update stream
     */
    private function getTracks($ids, $hashMap, &$stream)
    {
        $url = "tracks/?ids=" . implode(",", $ids);
        $r = $this->getSpotifyRequest($url);

        if( $r["tracks"] ){
            for($j=0;$j<count($r["tracks"]);$j++){
                $this->updateTrack($r["tracks"][$j], $hashMap[$r["tracks"][$j]["id"]], $stream);
            }
        }
    }

    /**
     * @param $artist_id int
     * @param $stream DatabaseStream
     * @return bool
     */
    private function getLastFMArtistImages($artist_id, $artist_name, &$stream)
    {
        $artist = $artist_name;
        $r = json_decode(HttpRequest::sendGetRequest(self::lastFMUrl . "?method=artist.getInfo&format=json&api_key=" . self::lastFMKey . "&artist=" . urlencode($artist)), true);

        if( $r && $r["artist"] && $r["artist"]["image"] ){
            $extralarge = null;
            $medium = null;

            foreach ($r["artist"]["image"] as $value) {
                if( $value["size"] == "extralarge" && $value["#text"] && strlen($value["#text"]) > 1 ){
                    $extralarge = $value["#text"];
                }
                else if( $value["size"] == "medium" && $value["#text"] && strlen($value["#text"]) > 1 ){
                    $medium = $value["#text"];
                }
            }

            if( $extralarge && $medium ){
                $stream->add([
                    "id" => $artist_id,
                    "image" => $extralarge,
                    "image_small" => $medium
                ]);

                return true;
            }
        }

        $stream->add([
            "id" => $artist_id,
            "image" => "",
            "image_small" => ""
        ]);

        return false;
    }

    public function updateArtistsLastFMImages()
    {
        $this->log->write("Task started!");

        $this->db->where ("image IS NULL AND popularity >= ?", [self::minArtistPopularity]);
        $artists = $this->db->ArrayBuilder()->get(Config::table_update_artists, self::amountLastFMImageUpdate, ["id", "name"]);
        $stream = new DatabaseStream(Config::table_update_artists, ["id", "image", "image_small"], DatabaseStream::UPDATE, ["spotify_id","name","popularity","image_background","last_update","last_update_related_calculated","last_update_related","last_update_top"]);

        foreach ($artists as $artist){
            $this->getLastFMArtistImages($artist["id"], $artist["name"], $stream);
        }

        $stream->commit();

        $this->log->write("Task finished!");
    }

    public function discoverFromPlaylists()
    {
        $this->log->write("Task started!");

        $time = time();
        $updateTracks = new DatabaseStream(Config::table_update_tracks, ["id","song_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","is_remix","is_acoustic","duration","last_update"], DatabaseStream::UPDATE, ["spotify_id","release_date","created"]);
        $insertTracks = new DatabaseStream(Config::table_update_tracks, ["spotify_id", "song_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","is_remix","is_acoustic","duration","last_update","release_date","created"]);

        $albumStream = new CustomStream(function($data) use (&$insertTracks){
            $albumIds = array_map(function($el){
                return $el["album"]["id"];
            },$data);

            $this->getAlbums($albumIds);

            foreach ($data as $obj)
            {
                $this->createTrack($obj, $insertTracks);
            }
        });
        $albumStream->setBufferSize(20);

        $r = $this->getSpotifyRequest("browse/categories/toplists/playlists");

        if( $r["playlists"] && $r["playlists"]["items"] ){
            for ($i=0; $i < count($r["playlists"]["items"]); $i++) {
                $item = $r["playlists"]["items"][$i];

                $_r = $this->getSpotifyRequest("users/spotify/playlists/" . $item["id"] . "/tracks");

                if( $_r["items"] && count($_r["items"]) > 0 ){
                    for ($j=0; $j < count($_r["items"]); $j++) {
                        $_item = $_r["items"][$j];

                        if( $_item["track"] && $_item["track"]["popularity"] ){
                            $track = $_item["track"];

                            $this->db->where ("spotify_id = ?", [$track["id"]]);
                            $dbTrack = $this->db->getOne(Config::table_update_tracks);

                            if( $dbTrack ){
                                if( intval($dbTrack["last_update"]) < $time - self::updateSongAfter ){
                                    $this->updateTrack($track, $dbTrack, $updateTracks);
                                }
                            }
                            else{
                                // Check if album exists
                                $this->db->where ("spotify_id = ?", [$track["album"]["id"]]);
                                $dbAlbum = $this->db->getOne(Config::table_update_albums);

                                if( $dbAlbum )
                                {
                                    $this->createTrack($track, $insertTracks, $dbAlbum["release_date"]);
                                }
                                else{
                                    $albumStream->add($track);
                                }
                            }
                        }
                    }
                }
            }

            $albumStream->commit();
            $updateTracks->commit();
            $insertTracks->commit();

            $this->log->write("Task finished!");
        }
    }

    public function updateTracks()
    {
        $this->log->write("Task started!");
        $time = time();

        $this->db->where ("last_update = '0' OR last_update <= ?", [$time - self::updateTrackAfter]);
        $tracks = $this->db->ArrayBuilder()->get(Config::table_update_tracks, self::amountTracksUpdate);
        
        $updateTracks = new DatabaseStream(Config::table_update_tracks, ["id","last_update"], DatabaseStream::UPDATE, ["song_id","spotify_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","release_date","duration","is_remix","is_acoustic","created"]);

        foreach ($tracks as $track){
            $updateTracks->add([$track["id"],$time]);
        }

        $updateTracks->commit();
        $updateTracks = new DatabaseStream(Config::table_update_tracks, ["id","song_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","is_remix","is_acoustic","duration","last_update"], DatabaseStream::UPDATE, ["spotify_id","release_date","created"]);

        $songStream = new CustomStream(function($data) use (&$updateTracks){
            $ids = array_map(function($el){
                return $el["spotify_id"];
            }, $data);

            $hashMap = [];

            foreach ($data as $obj){
                $hashMap[$obj["spotify_id"]] = $obj;
            }

            $this->getTracks($ids, $hashMap,$updateTracks);
        });
        $songStream->setBufferSize(50);

        foreach ($tracks as $track){
            $songStream->add($track);
        }

        $songStream->commit();
        $updateTracks->commit();

        $this->log->write("Task finished!");
    }

    public function updateRelatedArtists($useMutexStream = TRUE)
    {
        $this->log->write("Task started!");
        $mutexStream = null;
        $time = time();
    
        //$this->db->where ("last_update_related = '0' AND (popularity >= ? OR popularity = -1)", [self::minArtistPopularity]);
        if( $useMutexStream ){
            $that = $this;
            $mutexStream = new MutexStream("updateRelatedArtists", function () use (&$that){
                $time = time();

                $that->db->where ("(last_update_related = '0' OR last_update_related <= ?) AND (popularity >= ?)", [$time - SpotifyUpdateCrawler::updateRelatedAfter, SpotifyUpdateCrawler::minArtistPopularity]);

                return $that->db->ArrayBuilder()->get(Config::table_update_artists, 1500, ["id", "spotify_id"]);
            });
            
            //$mutexStream->reset();

            try{
                $mutexStream->lock();
                $artists = $mutexStream->getData(self::amountRelatedUpdate, false);
            }
            catch(MutexIsLockedException $e){
                $this->log->write("Error: Mutex locked!");
                return;
            }
        }
        else{
            $this->db->where ("(last_update_related = '0' OR last_update_related <= ?) AND (popularity >= ?)", [$time - self::updateRelatedAfter, self::minArtistPopularity]);
            $artists =  $this->db->ArrayBuilder()->get(Config::table_update_artists, self::amountRelatedUpdate, ["id", "spotify_id"]);
        }
        
        $updateArtists = new DatabaseStream(Config::table_update_artists, ["id","last_update_related"], DatabaseStream::UPDATE, ["spotify_id","name","popularity","image","image_background","image_small","last_update","last_update_related_calculated","last_update_top"]);
        $deleteRelated = new DatabaseStream(Config::table_update_related, "from_id", DatabaseStream::DELETE);

        foreach ($artists as $artist){
            $updateArtists->add([$artist["id"],$time]);
            $deleteRelated->add($artist["id"]);
        }

        $updateArtists->commit();
        $deleteRelated->commit();
        
        if($useMutexStream){
            $mutexStream->unlock();
        }
        
        $insertRelated = new DatabaseStream(Config::table_update_related, ["from_id","to_id","importance"]);
        $updateArtist = new DatabaseStream(Config::table_update_artists, ["id","popularity","image_background","last_update"], DatabaseStream::UPDATE, ["spotify_id","name","image","image_small","last_update_related_calculated","last_update_related","last_update_top"]);
        
        foreach ($artists as $artist){
            $r = $this->getSpotifyRequest("artists/" . $artist["spotify_id"] . "/related-artists");

            if( $r["artists"] ){
                for ($i=0; $i < count($r["artists"]); $i++) {
                    $_artist = $r["artists"][$i];
                    $to_artist = null;

                    $this->db->where ("spotify_id = ?", [$_artist["id"]]);
                    $toArtist = $this->db->getOne(Config::table_update_artists);

                    if( $toArtist ){
                        $insertRelated->add([$artist["id"], $toArtist["id"], ($i + 1)]);

                        // Only Update if necessary
                        if( intval($toArtist["last_update"]) < $time - self::updateArtistAfter ){
                            $cImages = count($_artist["images"]);

                            if( $cImages > 2 ){
                                $updateArtist->add([$toArtist["id"], $_artist["popularity"], $_artist["images"][$cImages-2]["url"], $time]);
                            }
                            else{
                                $updateArtist->add([$toArtist["id"], $_artist["popularity"], NULL, $time]);
                            }
                        }
                    }
                    else{
                        $cImages = count($_artist["images"]);
                        $image = NULL;

                        if( $cImages > 2 ){
                            $image = $_artist["images"][$cImages-2]["url"];
                        }

                        $artistId = $this->createNewArtist($_artist["id"], $_artist["name"], $image, $_artist["popularity"]);
                        $insertRelated->add([$artist["id"], $artistId, ($i + 1)]);
                    }
                }
            }
        }

        $insertRelated->commit();
        $updateArtist->commit();

        $this->log->write("Task finished!");
    }

    public function updateCharts()
    {
        // last two years
        $min_release_date = time() - 63072000;
    
        // First Delete
        $this->db->rawQuery("DELETE FROM " . Config::table_update_charts);
    
        // Alle
        $result = $this->db->ObjectBuilder()->rawQuery("SELECT s.song_id as id, MAX(s.current_popularity) as hotness FROM " . Config::table_update_tracks . " as s WHERE s.release_date >= '" . $min_release_date .  "' GROUP BY s.song_id ORDER BY hotness DESC LIMIT 30");
        $place = 0;
    
        foreach($result as $row){
            $this->db->insert(Config::table_update_charts, array("genre_id" => 0,
                "song_id" => $row->id,
                "place" => $place,
                "created" => time()));
            $place++;
        }
    
        // House
        $result = $this->db->ObjectBuilder()->rawQuery("SELECT s.song_id as id, MAX(s.current_popularity) as hotness FROM " . Config::table_update_tracks . " as s WHERE" .
            " s.release_date >= '" . $min_release_date .  "' AND " .
            "(s.artist_id IN (SELECT to_id FROM " . Config::table_update_related_calculated . " WHERE from_id IN ('470','442') AND difference <= 0.5) OR " .
            "s.artist_id2 IN (SELECT to_id FROM " . Config::table_update_related_calculated . " WHERE from_id IN ('470','442') AND difference <= 0.5) OR " .
            "s.artist_id3 IN (SELECT to_id FROM " . Config::table_update_related_calculated . " WHERE from_id IN ('470','442') AND difference <= 0.5)) ".
            "GROUP BY s.song_id ORDER BY hotness DESC LIMIT 30");
    
        $place = 0;
    
        foreach($result as $row){
            $this->db->insert(Config::table_update_charts, array("genre_id" => 1,
                "song_id" => $row->id,
                "place" => $place,
                "created" => time()));
            $place++;
        }
    
        // Rock
        $result = $this->db->ObjectBuilder()->rawQuery("SELECT s.song_id as id, MAX(s.current_popularity) as hotness FROM " . Config::table_update_tracks . " as s WHERE" .
            " s.release_date >= '" . $min_release_date .  "' AND " .
            "s.artist_id IN (SELECT to_id FROM " . Config::table_update_related_calculated . " WHERE from_id = 20854 AND difference <= 0.5) AND (s.is_remix = '0' OR s.is_remix IS NULL)".
            "GROUP BY s.song_id ORDER BY hotness DESC LIMIT 30");
    
        $place = 0;
    
        foreach($result as $row){
            $this->db->insert(Config::table_update_charts, array("genre_id" => 2,
                "song_id" => $row->id,
                "place" => $place,
                "created" => time()));
            $place++;
        }
    
        // Pop
        $result = $this->db->ObjectBuilder()->rawQuery("SELECT s.song_id as id, MAX(s.current_popularity) as hotness FROM " . Config::table_update_tracks . " as s WHERE" .
            " s.release_date >= '" . $min_release_date .  "' AND " .
            "s.artist_id IN (SELECT to_id FROM " . Config::table_update_related_calculated . " WHERE from_id = 376 AND difference <= 0.5) ".
            "GROUP BY s.song_id ORDER BY hotness DESC LIMIT 30");
    
        $place = 0;
    
        foreach($result as $row){
            $this->db->insert(Config::table_update_charts, array("genre_id" => 3,
                "song_id" => $row->id,
                "place" => $place,
                "created" => time()));
            $place++;
        }
    
        // Hip hop
        $result = $this->db->ObjectBuilder()->rawQuery("SELECT s.song_id as id, MAX(s.current_popularity) as hotness FROM " . Config::table_update_tracks . " as s WHERE" .
            " s.release_date >= '" . $min_release_date .  "' AND " .
            "s.artist_id IN (SELECT to_id FROM " . Config::table_update_related_calculated . " WHERE from_id = 22 AND difference <= 0.5) AND (s.is_remix = '0' OR s.is_remix IS NULL)".
            "GROUP BY s.song_id ORDER BY hotness DESC LIMIT 30");
    
        $place = 0;
    
        foreach($result as $row){
            $this->db->insert(Config::table_update_charts, array("genre_id" => 4,
                "song_id" => $row->id,
                "place" => $place,
                "created" => time()));
            $place++;
        }
    }

    public function updateArtistsTopSongs($useMutexStream = TRUE)
    {
        $this->log->write("Task started!");
        $mutexStream = null;
        $time = time();

        //$this->db->where ("last_update_related = '0' AND (popularity >= ? OR popularity = -1)", [self::minArtistPopularity]);
        if( $useMutexStream ){
            $that = $this;
            $mutexStream = new MutexStream("updateArtistsTopSongs", function () use (&$that){
                $time = time();

                $that->db->where ("(last_update_top = '0' OR last_update_top < ?) AND popularity >= ?", [$time - SpotifyUpdateCrawler::updateArtistTopSongsAfter, SpotifyUpdateCrawler::minArtistPopularity]);

                return $that->db->ArrayBuilder()->get(Config::table_update_artists, 1500, ["id", "spotify_id"]);
            });

            //$mutexStream->reset();

            try{
                usleep(rand(1000,100000));

                $mutexStream->lock();

                $artists = $mutexStream->getData(self::amountTopSongsUpdate, false);
            }
            catch(MutexIsLockedException $e){
                $this->log->write("Error: Mutex locked!");
                return;
            }
        }
        else{
            $this->db->where ("(last_update_top = '0' OR last_update_top < ?) AND popularity >= ?", [$time - self::updateArtistTopSongsAfter, self::minArtistPopularity]);
            $artists = $this->db->ArrayBuilder()->get(Config::table_update_artists, self::amountTopSongsUpdate, ["id", "spotify_id"]);
        }

        $updateArtists = new DatabaseStream(Config::table_update_artists, ["id","last_update_top"], DatabaseStream::UPDATE, ["spotify_id","name","popularity","image","image_background","image_small","last_update","last_update_related","last_update_related_calculated"]);

        foreach ($artists as $artist){
            $updateArtists->add([$artist["id"],$time]);
        }

        $updateArtists->commit();

        if($useMutexStream){
            $mutexStream->unlock();
        }

        $updateTracks = new DatabaseStream(Config::table_update_tracks, ["id","song_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","is_remix","is_acoustic","duration","last_update"], DatabaseStream::UPDATE, ["spotify_id","release_date","created"]);
        $insertTracks = new DatabaseStream(Config::table_update_tracks, ["spotify_id", "song_id","isrc","current_popularity","crossed_popularity","artist_id","artist_pop","artist_id2","artist_id3","is_remix","is_acoustic","duration","last_update","release_date","created"]);

        $albumStream = new CustomStream(function($data) use (&$insertTracks){
            $albumIds = array_map(function($el){
                return $el["album"]["id"];
            },$data);

            $this->getAlbums($albumIds);

            foreach ($data as $obj)
            {
                $this->createTrack($obj, $insertTracks);
            }
        });

        $albumStream->setBufferSize(20);

        foreach ($artists as $artist) {
            $r = $this->getSpotifyRequest("artists/" . $artist["spotify_id"] . "/top-tracks?country=DE");

            if( $r["tracks"] ){
                for ($i=0; $i < count($r["tracks"]); $i++) {
                    $track = $r["tracks"][$i];

                    $this->db->where ("spotify_id = ?", [$track["id"]]);
                    $dbTrack = $this->db->getOne(Config::table_update_tracks);

                    if( $dbTrack ){
                        if( intval($dbTrack["last_update"]) < $time - self::updateSongAfter ){
                            $this->updateTrack($track , $dbTrack, $updateTracks);
                        }
                    }
                    else{
                        // Check if album exists
                        $this->db->where ("spotify_id = ?", [$track["album"]["id"]]);
                        $dbAlbum = $this->db->getOne(Config::table_update_albums);

                        if( $dbAlbum )
                        {
                            $this->createTrack($track, $insertTracks, $dbAlbum["release_date"]);
                        }
                        else{
                            $albumStream->add($track);
                        }
                    }
                }
            }
        }

        $albumStream->commit();
        $updateTracks->commit();
        $insertTracks->commit();

        $this->log->write("Task finished!");
    }

    public function test()
    {
        $test = new CustomStream(function($str){
            $this->printMe($str);
        });

        $test->add("test");
        $test->commit();
    }

    public function printMe($str)
    {
        print_r($str);
    }

    public function resetArtistCalculation(){
        $this->db->rawQuery("UPDATE ".Config::table_update_artists." SET last_update_related_calculated = '0'");
    }

    public function resetArtistRelatedCrawling(){
        $this->db->rawQuery("UPDATE ".Config::table_update_artists." SET last_update_related = '0'");
    }

    public function resetSongCrawling(){
        $this->db->rawQuery("UPDATE ".Config::table_update_tracks." SET last_update = '0'");
    }
}