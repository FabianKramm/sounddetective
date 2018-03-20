<?php
namespace SoundDetective;

use Memcached;
use SoundDetective\Util\HttpRequest;
use SoundDetective\Util\SoundDetectiveDB;
use SoundDetective\Util\Util;
use SoundDetective\Util\Config;
use Exception;
    
header("Access-Control-Allow-Origin: " . Config::playUrl);
header("Access-Control-Allow-Credentials: true");

class Controller{
    public static function reportVideo($song_id){
        $db = SoundDetectiveDB::getInstance();
        $song_id = $db->escape($song_id);

        $db->where("song_id = ? AND ip = ?", [$song_id, $_SERVER['REMOTE_ADDR']]);
        $row = $db->ObjectBuilder()->getOne(Config::table_wrong_video, ["song_id", "ip"]);

        if( $row ){
            return;
        }
        else{
            $db->insert(Config::table_wrong_video, array("song_id" => $song_id,
                                                                 "ip" => $_SERVER['REMOTE_ADDR'],
                                                                 "created" => time()));
        }
    }

    public static function getPopularArtists(){
        $memcached = new Memcached;
        $memcached->addServer('localhost', 11211) or die ("Could not connect to Memcached");

        $cached = $memcached->get("getPopularArtists");

        if ( !$cached ){
            $db = SoundDetectiveDB::getInstance();

            $db->where("1=1");
            $db->orderBy("popularity", "DESC");
            $result = $db->ObjectBuilder()->get(Config::table_artists, 10, ["id", "name", "image_small"]);
            $output = [];

            foreach( $result as $row ){
                $output[] = ["id" => strval($row->id), "name" => $row->name, "image_small" => $row->image_small];
            }

            $cached = json_encode($output);
            $memcached->set("getPopularArtists", $cached, time() + 32000);
        }

        echo $cached;
    }

    public static function getArtistDetails($artist_id){
        $memcached = new Memcached;
        $memcached->addServer('localhost', 11211) or die ("Could not connect to Memcached");

        $cached = $memcached->get("getArtistDetails_" . strval($artist_id));

        if( !$cached ){
            $db = SoundDetectiveDB::getInstance();
            $artist_id = $db->escape($artist_id);
            $returnArr = [];

            $returnArr["id"] = $artist_id;

            // Basic Info
            $db->where("id = ?", [$artist_id]);
            $row = $db->ObjectBuilder()->getOne(Config::table_artists, ["name", "image", "image_background"]);

            if( $row ){
                $returnArr["info"] = ["name" => $row->name, "image" => $row->image, "image_background" => $row->image_background];
            }
            else{
                return false;
            }

            // Songs
            $result = $db->ObjectBuilder()->rawQuery("SELECT MIN(sm.title) as title,MIN(sm.artists) as artist_name,MIN(s.artist_id) as artist_id,MIN(s.artist_id2) as artist_id2,MIN(s.artist_id3) as artist_id3,MIN(s.spotify_id) as spotify_id,sm.id as db_id,MAX(s.crossed_popularity) as popularity,MIN(s.release_date) as release_date FROM " . Config::table_songs . " as sm INNER JOIN (SELECT p.song_id,p.artist_id,p.artist_id2,p.artist_id3,p.spotify_id,p.crossed_popularity,p.release_date FROM " . Config::table_tracks . " as p WHERE p.artist_id = '" . $db->escape($artist_id) . "' OR p.artist_id2 = '" . $db->escape($artist_id) . "' ) as s ON sm.id = s.song_id GROUP BY db_id");

            $returnArr["tracks"] = [];

            foreach( $result as $row ){
                $title = $row->title;
                $found = false;

                // Check if track already exists
                foreach ($returnArr["tracks"] as $value) {
                    if( $value["title"] == $title ){
                        $found = true;
                        break;
                    }
                }

                if( !$found ){
                    $returnArr["tracks"][] = ["title" => strval($title), "artist_name" => strval($row->artist_name), "available_spotify_id" => strval($row->spotify_id), "db_id" => strval($row->db_id), "db_artist_id" => strval($row->artist_id), "db_artist_id2" => strval($row->artist_id2), "db_artist_id3" => strval($row->artist_id3), "popularity" => strval($row->popularity), "release_date" => strval($row->release_date)];
                }
            }

            // Related Artists
            $result = $db->ObjectBuilder()->rawQuery("SELECT id,name,image FROM " . Config::table_artists . " as a INNER JOIN (SELECT to_id FROM " . Config::table_artists_related_calculated . " WHERE from_id = '$artist_id' ORDER BY difference ASC LIMIT 25) as b ON a.id = b.to_id WHERE id != '$artist_id'");

            $returnArr["related"] = [];

            foreach( $result as $row ){
                $returnArr["related"][] = ["id" => strval($row->id), "name" => strval($row->name), "image" => strval($row->image)];
            }

            // TODO: Fallback Spotify Artists

            // Genres (Electronic,Pop etc.)
            $result = $db->ObjectBuilder()->rawQuery("SELECT n.name FROM " . Config::table_artists_genre . " as s INNER JOIN " . Config::table_genres . " as n ON s.genre_id = n.id WHERE s.artist_id = '$artist_id' AND type = '0' AND percentage >= '0.5'");

            $returnArr["genres"] = [];

            foreach( $result as $row ){
                $returnArr["genres"][] = ["name" => $row->name];
            }

            $cached = json_encode($returnArr);
            $memcached->set("getArtistDetails_" . strval($artist_id), $cached, time() + 1800);
        }

        echo $cached;
    }

    public static function getDetectives(){
        $user_id = UserSession::getUserId();
        $db = SoundDetectiveDB::getInstance();

        if( !$user_id  ){
            // Check if we cached it
            $memcached = new Memcached;
            $memcached->addServer('localhost', 11211) or die ("Could not connect to Memcached");

            $cached = $memcached->get("getDetectives");

            if( !$cached ){
                $db->where("owner_id = '0'");
                $db->orderBy("last_update", "DESC");
                $result = $db->ObjectBuilder()->get(Config::table_detective, 100, ["uuid","owner_id","public_type","image","name"]);

                $returnArr = [];

                foreach( $result as $row ){
                    $returnArr[] = ["uuid" => strval($row->uuid), "image" => strval($row->image), "name" => strval($row->name), "owner_id" => strval($row->owner_id), "public_type" => strval($row->public_type)];
                }

                $cached = json_encode($returnArr);

                // Cache it
                $memcached->set("getDetectives", $cached, time() + 3600);
            }

            echo $cached;
        }
        else{
            $db->where("owner_id = '0' OR owner_id = ?", [$user_id]);
            $db->orderBy("last_update", "DESC");
            $result = $db->ObjectBuilder()->get(Config::table_detective, 100, ["uuid","owner_id","public_type","image","name"]);

            $returnArr = [];

            foreach( $result as $row ){
                $returnArr[] = ["uuid" => strval($row->uuid), "image" => strval($row->image), "name" => strval($row->name), "owner_id" => strval($row->owner_id), "public_type" => strval($row->public_type)];
            }

            echo json_encode($returnArr);
        }
    }

    public static function getDetectiveDetails($uuid, $print = true){
        $db = SoundDetectiveDB::getInstance();

        $db->where("uuid = ?", [$uuid]);
        $row = $db->ObjectBuilder()->getOne(Config::table_detective, ["id","uuid","owner_id","image","name","min_song_pop","max_song_pop","min_artist_pop","max_artist_pop","min_release_date","max_release_date","exclude_remix","exclude_acoustic","exclude_collection"]);

        if( $row ){
            $returnArr = ["uuid" => strval($row->uuid), "artists" => [], "image" => strval($row->image), "name" => strval($row->name), "owner_id" => strval($row->owner_id), "min_song_pop" => strval($row->min_song_pop), "max_song_pop" => strval($row->max_song_pop), "min_artist_pop" => strval($row->min_artist_pop), "max_artist_pop" => strval($row->max_artist_pop), "min_release_date" => strval($row->min_release_date), "max_release_date" => strval($row->max_release_date), "exclude_remix" => strval($row->exclude_remix), "exclude_acoustic" => strval($row->exclude_acoustic), "exclude_collection" => strval($row->exclude_collection)];

            $result = $db->ObjectBuilder()->rawQuery("SELECT s.id,s.name,sa.distance,s.image_small FROM " . Config::table_detective_artists . " as sa INNER JOIN " . Config::table_artists . " as s ON sa.artist_id = s.id WHERE sa.detective_id = '" . $row->id . "'");

            foreach( $result as $obj ){
                $returnArr["artists"][] = ["id" => strval($obj->id), "name" => strval($obj->name), "distance" => strval($obj->distance), "image_small" => strval($obj->image_small)];
            }

            if( $print ){
                echo json_encode($returnArr);
            }
            else{
                return $returnArr;
            }
        }
    }

    public static function applyDetective($uuid, $excludeSongs = NULL){
        /*
         * Test:
         SELECT s.id as db_id,a.title,a.artist as artist_name,s.spotify_id,s.artist_id,s.crossed_popularity FROM AA_MUSIC_SONGS_SPOTIFY as a INNER JOIN (SELECT s.song_id as id,s.spotify_id,s.crossed_popularity,s.artist_id FROM AA_MUSIC_SONGS_POPULARITY as s WHERE ((s.artist_id IN (SELECT to_id FROM A_MUSIC_ARTISTS_SPOTIFY_RELATED_CALCULATED WHERE 1=0  OR (from_id = 10 AND difference <= '0.25') OR (from_id = 4 AND difference <= '0.25'))) OR ( 1=0  OR (s.artist_id = 7))) AND s.song_id NOT IN (SELECT song_id FROM AA_MUSIC_DETECTIVE_EXCLUDE_SONGS WHERE detective_id = '1')AND s.artist_id NOT IN (SELECT artist_id FROM AA_MUSIC_DETECTIVE_EXCLUDE_ARTISTS WHERE detective_id = '1') AND s.crossed_popularity >= '50' AND s.artist_pop > '30') as s ON a.id = s.id
         */
        $db = SoundDetectiveDB::getInstance();

        $db->where("uuid = ?", [$uuid]);
        $obj = $db->ObjectBuilder()->getOne(Config::table_detective, ["id","owner_id","min_song_pop","max_song_pop","min_artist_pop","max_artist_pop","min_release_date","max_release_date","exclude_remix","exclude_acoustic","exclude_collection"]);

        if( $obj ){
            if( intval($obj->owner_id) == 0 ){
                // Check if we cached it
                $memcached = new Memcached;
                $memcached->addServer('localhost', 11211) or die ("Could not connect to Memcached");

                $cached = $memcached->get($uuid);

                if( !$cached ){
                    $artistDistance = [];
                    $artistSolely = [];

                    $db->where("detective_id = ?", [$obj->id]);
                    $result = $db->ObjectBuilder()->get(Config::table_detective_artists, 50, ["artist_id","distance"]);

                    foreach( $result as $row ){
                        if( $row->distance >= 0 ){
                            $artistDistance[] = [$row->artist_id, $row->distance];
                        }
                        else{
                            $artistSolely[] = $row->artist_id;
                        }
                    }

                    $cached = self::searchWithOptions($obj->id, $artistSolely, $artistDistance, $obj->min_song_pop, $obj->max_song_pop, $obj->min_artist_pop, $obj->max_artist_pop, $obj->min_release_date, $obj->max_release_date, $obj->exclude_remix, $obj->exclude_acoustic, $excludeSongs, $obj->exclude_collection, $uuid);

                    // store for 1 hour
                    $memcached->set($uuid, $cached, time() + 3600);
                }

                echo $cached;
            }
            else{
                if( intval($obj->owner_id) != intval(UserSession::getUserId()) ){
                    return null;
                }

                $artistDistance = [];
                $artistSolely = [];

                $db->where("detective_id = ?", [$obj->id]);
                $result = $db->ObjectBuilder()->get(Config::table_detective_artists, 50, ["artist_id","distance"]);

                foreach( $result as $row ){
                    if( $row->distance >= 0 ){
                        $artistDistance[] = [$row->artist_id, $row->distance];
                    }
                    else{
                        $artistSolely[] = $row->artist_id;
                    }
                }

                echo self::searchWithOptions($obj->id, $artistSolely, $artistDistance, $obj->min_song_pop, $obj->max_song_pop, $obj->min_artist_pop, $obj->max_artist_pop, $obj->min_release_date, $obj->max_release_date, $obj->exclude_remix, $obj->exclude_acoustic, $excludeSongs, $obj->exclude_collection, $uuid);
            }
        }
    }

    public static function applyTempDetective($search_artists = NULL, $min_song_pop = NULL, $max_song_pop = NULL, $min_artist_pop = NULL, $max_artist_pop = NULL, $min_release_date = NULL, $max_release_date = NULL, $exclude_remix = NULL, $exclude_acoustic = NULL, $exclude_songs = NULL){
        $artistDistance = [];
        $artistSolely = [];
        $db = SoundDetectiveDB::getInstance();

        $artists = explode(";", $search_artists);

        if( count($artists) > 8 ){
            return;
        }

        foreach ($artists as $value) {
            $exploded = explode(",", $value);

            if( count($exploded) != 2 ){
                continue;
            }

            if( floatval($exploded[1]) <= 0 ){
                $artistSolely[] = $db->escape($exploded[0]);
            }
            else{
                $artistDistance[] = [$db->escape($exploded[0]), $db->escape($exploded[1])];
            }
        }

        echo self::searchWithOptions(NULL, $artistSolely, $artistDistance, $min_song_pop, $max_song_pop, $min_artist_pop, $max_artist_pop, $min_release_date, $max_release_date, $exclude_remix, $exclude_acoustic, $exclude_songs);
    }

    public static function searchWithOptions($detective_id = NULL, $artistSolely = NULL, $artistDistance = NULL, $min_song_pop = NULL, $max_song_pop = NULL, $min_artist_pop = NULL, $max_artist_pop = NULL, $min_release_date = NULL, $max_release_date = NULL, $exclude_remix = NULL, $exclude_acoustic = NULL, $exclude_songs = NULL, $exclude_collection = NULL, $uuid = NULL){
        $db = SoundDetectiveDB::getInstance();
        $query = "SELECT SQL_CALC_FOUND_ROWS s.song_id,s.artist_id FROM " . Config::table_tracks . " as s WHERE (";

        if( count($artistDistance) ){
            $query .= "(s.artist_id IN (SELECT to_id FROM " . Config::table_artists_related_calculated . " WHERE 1=0 ";

            foreach ($artistDistance as $key => $value) {
                $query .= " OR (from_id = " . $value[0] . " AND difference <= '" . $value[1] . "')";
            }

            $query .= "))";

            /*$query .= " OR (s.artist_id2 IN (SELECT to_id FROM " . Config::table_artists_related_calculated . " WHERE 1=0 ";

            foreach ($artistDistance as $key => $value) {
                $query .= " OR (from_id = " . $value[0] . " AND difference <= '" . $value[1] . "')";
            }

            $query .= "))";

            $query .= " OR (s.artist_id3 IN (SELECT to_id FROM " . Config::table_artists_related_calculated . " WHERE 1=0 ";

            foreach ($artistDistance as $key => $value) {
                $query .= " OR (from_id = " . $value[0] . " AND difference <= '" . $value[1] . "')";
            }

            $query .= "))";*/
        }
        else{
            $query .= " 1=0 ";
        }

        if( count($artistSolely) ){
            $query .= " OR ( 1=0 ";

            foreach ($artistSolely as $key => $value) {
                $query .= " OR (s.artist_id = " . $value. ")";
            }

            $query .= ")";
        }

        if( !count($artistDistance) && !count($artistSolely) ){
            $query .= " OR 1=1) ";
        }
        else{
            $query .= ") ";
        }

        if( $detective_id ){
            $query .= "AND s.song_id NOT IN (SELECT song_id FROM " . Config::table_detective_exclude_songs . " WHERE detective_id = '".$detective_id."')" .
                  "AND s.artist_id NOT IN (SELECT artist_id FROM " . Config::table_detective_exclude_artists . " WHERE detective_id = '".$detective_id."')";
        }

        if( $exclude_songs ){
            $exclude_songs = explode(",", $exclude_songs);
            $excludeString = "";
            $c = count($exclude_songs);

            for ($i=0;$i<$c;$i++) {
                if( $i + 1 == $c ){
                    $excludeString .= "'" . $db->escape($exclude_songs[$i]) . "'";
                }
                else{
                    $excludeString .= "'" . $db->escape($exclude_songs[$i]) . "',";
                }
            }

            $query .=  " AND s.song_id NOT IN (" . $excludeString . ")";
        }

        if( $min_release_date ){
            if( intval($min_release_date) < 0 ){
                $min_release_date = time() + intval($min_release_date);
            }

            $query .=  " AND s.release_date >= '" . $min_release_date . "'";
        }

        if( $max_release_date ){
            if( intval($max_release_date) < 0 ){
                $max_release_date = time() + intval($max_release_date);
            }

            $query .=  " AND s.release_date <= '" . $max_release_date . "'";
        }

        if( !$min_song_pop ){
            $min_song_pop = 10;
        }

        $min_song_pop = max(10, $min_song_pop);

        //if( $min_song_pop ){
        //    $query .=  " AND s.crossed_popularity >= '" . $min_song_pop . "'";
        //}

        //if( $max_song_pop ){
        //    $query .=  " AND s.crossed_popularity <= '" . $max_song_pop . "'";
        //}

        $query .=  " AND s.crossed_popularity >= '15'";

        if( !$min_artist_pop ){
            $min_artist_pop = 20;
        }

        $min_artist_pop = max(20, $min_artist_pop);


        if( $min_artist_pop ){
            $query .=  " AND s.artist_pop >= '" . $min_artist_pop . "'";
        }

        if( $max_artist_pop ){
            $query .=  " AND s.artist_pop <= '" . $max_artist_pop . "'";
        }

        if( $exclude_remix ){
            $query .=  " AND (s.is_remix = '0' OR s.is_remix IS NULL)";
        }

        if( $exclude_acoustic ){
            $query .=  " AND (s.is_acoustic = '0' OR s.is_acoustic IS NULL)";
        }

        $user_id = UserSession::getUserId();

        if( $exclude_collection && $user_id ){
            $query .=  " AND s.song_id NOT IN (SELECT song_id FROM " . Config::table_user_collection . " WHERE user_id = $user_id)";
        }

        $query .= " ORDER BY s.crossed_popularity DESC LIMIT 3000";

        // Save query
        /*$db->insert(Config::table_user_track, [
            'ip' => $_SERVER["REMOTE_ADDR"],
            'query' => $query,
            'time' => time()
        ]);*/
        

        $result = $db->ArrayBuilder()->rawQuery($query);
        $songCount = $db->completeRawQuery("SELECT FOUND_ROWS()")->fetch_row()[0];//min(3000, $db->completeRawQuery("SELECT FOUND_ROWS()")->fetch_row()[0]);

        $startOffset = ( $max_song_pop ) ? $max_song_pop : 100;
        $endOffset = ( $min_song_pop > 10 ) ? $min_song_pop : 0;

        $startOffset = min(2900, intval($songCount * Util::getPercentageFromPopularity($startOffset)));
        $endOffset = min(3000, intval($songCount * Util::getPercentageFromPopularity($endOffset)));

        //echo "MinSong: $min_song_pop MaxSong: $max_song_pop Anzahl: $songCount start: $startOffset end: $endOffset";

        $index = 0;
        $arr = [];
        $arr2 = [];

        foreach($result as $row){
            if( $index >= $startOffset && !isset($arr2[$row["song_id"]]) ){
                $arr[] = [strval($row["song_id"]), strval($row["artist_id"])];
                $arr2[$row["song_id"]] = 1;
            }

            $index++;

            if( $index >= $endOffset ){
                break;
            }
        }

        if($uuid && !$exclude_songs){
            return json_encode(["detective_info" => self::getDetectiveDetails($uuid, false), "detective_songs_detail" => [], "detective_songs" => $arr]);
        }
        else{
            return json_encode(["detective_songs_detail" => [], "detective_songs" => $arr]);
        }
    }

    public static function echoSongDetails($ids){
        $ids = explode(",", $ids);

        if( !count($ids) ){
            return;
        }

        foreach ($ids as $key => $value) {
            if( !is_numeric($value) ){
                return;
            }
        }

        echo json_encode(self::getSongDetails($ids));
    }

    private static function getSongDetails($ids){
        if( !count($ids) || count($ids) > 35 ){
            return [];
        }

        // Songs
        $db = SoundDetectiveDB::getInstance();
        $result = $db->ObjectBuilder()->rawQuery("SELECT MIN(sm.title) as title,MIN(sm.artists) as artist_name,MIN(s.artist_id) as artist_id,MIN(s.artist_id2) as artist_id2,MIN(s.artist_id3) as artist_id3,MIN(s.spotify_id) as spotify_id,sm.id as db_id,MIN(s.crossed_popularity) as popularity,MIN(s.release_date) as release_date FROM " . Config::table_songs . " as sm INNER JOIN " . Config::table_tracks . " as s ON sm.id = s.song_id WHERE sm.id IN (" . $db->escape(implode(",", $ids)) . ") GROUP BY sm.id");

        $returnArr = [];

        foreach( $result as $row ){
            $title = $row->title;
            $returnArr[] = ["title" => strval($title), "artist_name" => strval($row->artist_name), "available_spotify_id" => strval($row->spotify_id), "db_id" => strval($row->db_id), "db_artist_id" => strval($row->artist_id), "db_artist_id2" => strval($row->artist_id2), "db_artist_id3" => strval($row->artist_id3), "popularity" => strval($row->popularity), "release_date" => strval($row->release_date)];
        }

        return $returnArr;
    }

    public static function getArtistPoolCount($artists){
        $artistDistance = [];
        $artistSolely = [];

        $artists = explode(";", $artists);

        foreach ($artists as $value) {
            $artist = explode(",", $value);

            if( floatval($artist[1]) >= 0 ){
                $artistDistance[] = [$artist[0], floatval($artist[1])];
            }
            else{
                $artistSolely[] = $artist[0];
            }
        }

        if( count($artistDistance) ){
            $db = SoundDetectiveDB::getInstance();

            $query = "SELECT COUNT(*) as pool FROM (SELECT DISTINCT to_id FROM " . Config::table_artists_related_calculated . " WHERE 1=0 ";

            foreach ($artistDistance as $key => $value) {
                $query .= " OR (from_id = " . $db->escape($value[0]) . " AND difference <= '" . $db->escape($value[1]) . "')";
            }

            $query .= ") as s";

            $result = $db->ObjectBuilder()->rawQuery($query);

            if( $row = $result[0] ){
                echo intval($row->pool) + count($artistSolely);
                return true;
            }
        }

        echo count($artistSolely);
        return true;
    }

    public static function getDetectiveExcludes($uuid, $artists = NULL, $songs = NULL){
        $db = SoundDetectiveDB::getInstance();

        $db->where("uuid = ?", [$uuid]);
        $row = $db->ObjectBuilder()->getOne(Config::table_detective, ["id"]);

        if( $songs && $row ){
            $result = $db->ObjectBuilder()->rawQuery("SELECT id,title,artists as artist FROM " . Config::table_songs . " WHERE id IN (SELECT song_id FROM " . Config::table_detective_exclude_songs . " WHERE detective_id = " . $row->id . ")");
            $returnArr = [];

            foreach( $result as $row ){
                $returnArr[] = ["id" => strval($row->id), "artist" => strval($row->artist), "title" => strval($row->title)];
            }

            echo json_encode($returnArr);
            return true;
        }
        else if ( $artists && $row ){
            $result = $db->ObjectBuilder()->rawQuery("SELECT id,name,image_small FROM " . Config::table_artists . " WHERE id IN (SELECT artist_id FROM " . Config::table_detective_exclude_artists . " WHERE detective_id = " . $row->id . ")");
            $returnArr = [];

            foreach( $result as $row ){
                $returnArr[] = ["id" => strval($row->id), "name" => strval($row->name), "image_small" => strval($row->image_small)];
            }

            echo json_encode($returnArr);
            return true;
        }
    }

    public static function createDetective($name, $uuid = NULL, $search_artists = NULL, $min_song_pop = NULL, $max_song_pop = NULL, $min_artist_pop = NULL, $max_artist_pop = NULL, $min_release_date = NULL, $max_release_date = NULL, $exclude_remix = NULL, $exclude_acoustic = NULL, $exclude_collection = NULL, $exclude_songs = NULL, $exclude_artists = NULL, $image = NULL){
        $user_id = UserSession::getUserId();
        $db = SoundDetectiveDB::getInstance();
        $modify = false;
        $time = time();

        // Only logged in users can create detectives
        if( !$user_id ){
            return false;
        }

        $artists = explode(";", $search_artists);

        if( count($artists) > 8 ){
            return false;
        }

        if( strlen($name) <= 0 || strlen($name) > 18 ){
            return false;
        }

        if( !$uuid ){
            $uuid = uniqid();
        }
        else{
            // Check if we have permission
            $db->where("uuid = ?", [$uuid]);
            $row = $db->ObjectBuilder()->getOne(Config::table_detective, ["id", "owner_id"]);

            if( $row  ){
                if( intval($row->owner_id) == 0 ){
                    $uuid = uniqid();
                }
                else if( intval($row->owner_id) != $user_id ){
                    // You're not the owner!
                    return false;
                }
                else{
                    $modify = true;
                    $detective_id = intval($row->id);
                }
            }
            else{
                // This uuid doesn't exist
                return false;
            }
        }

        $insertArr = ["uuid" => $uuid,
                      "owner_id" => $user_id,
                      "name" => $name,
                      "created" => $time,
                      "last_update" => $time,
                      "last_used" => $time];
        if( !$image ){
            $insertArr["image"] = "img/SD.png"; // hardcode bois
        }
        else{
            $insertArr["image"] = $image;
        }

        if( $min_song_pop ){
            $insertArr["min_song_pop"] = intval($min_song_pop);
        }

        if( $max_song_pop ){
            $insertArr["max_song_pop"] = intval($max_song_pop);
        }

        if( $min_artist_pop ){
            $insertArr["min_artist_pop"] = intval($min_artist_pop);
        }

        if( $max_artist_pop ){
            $insertArr["max_artist_pop"] = intval($max_artist_pop);
        }

        if( $min_release_date ){
            $insertArr["min_release_date"] = intval($min_release_date);
        }

        if( $max_release_date ){
            $insertArr["max_release_date"] = intval($max_release_date);
        }

        if( $exclude_remix ){
            $insertArr["exclude_remix"] = 1;
        }
        else{
            $insertArr["exclude_remix"] = 0;
        }

        if( $exclude_acoustic ){
            $insertArr["exclude_acoustic"] = 1;
        }
        else{
            $insertArr["exclude_acoustic"] = 0;
        }

        if( $exclude_collection ){
            $insertArr["exclude_collection"] = 1;
        }
        else{
            $insertArr["exclude_collection"] = 0;
        }

        if( $modify ){
            $insertArr["id"] = $detective_id;
        }

        $db->replace(Config::table_detective, $insertArr);

        // Get detective_id if were not modifying
        if( !$modify ){
            $detective_id = $db->getInsertId();
        }

        // Insert search artists
        if( $search_artists ){
            if( $modify ){
                $db->rawQuery("DELETE FROM " . Config::table_detective_artists . " WHERE detective_id = '$detective_id'");
            }

            $values = [];

            $artists = explode(";", $search_artists);

            if( count($artists) > 0 ){
                foreach ($artists as $value) {
                    $entry = explode(",", $value);

                    if( count($entry) == 2 ){
                        $values[] = [$detective_id, $db->escape($entry[0]), $db->escape($entry[1])];
                    }
                    else{
                        // Strange artist formatting
                        continue;
                    }
                }
            }

            if( count($values) <= 10 ){
                $db->massInsert(Config::table_detective_artists, array("detective_id","artist_id","distance"), $values);
            }
            else{
                echo "Too many artists!!";
                return false;
            }
        }

        // Exclude Songs
        if( $exclude_songs !== NULL ){
            if( $modify ){
                $db->rawQuery("DELETE FROM " . Config::table_detective_exclude_songs . " WHERE detective_id = '$detective_id'");
            }

            $songs = explode(",", $exclude_songs);
            $values = [];

            if( count($songs) > 0 ){
                foreach ($songs as $value) {
                    if( is_numeric($value) ){
                        $values[] = [$detective_id, $db->escape($value), $time];
                    }
                }
            }

            if( count($values) < 15000 ){
                $db->massInsert(Config::table_detective_exclude_songs, array("detective_id","song_id","last_update"), $values);
            }
            else{
                echo "Too many excludes songs!!";
                return false;
            }
        }

        // Exclude Artists
        if( $exclude_artists !== NULL ){
            if( $modify ){
                $db->rawQuery("DELETE FROM " . Config::table_detective_exclude_artists . " WHERE detective_id = '$detective_id'");
            }

            $artists = explode(",", $exclude_artists);
            $values = [];

            if( count($artists) > 0 ){
                foreach ($artists as $value) {
                    if( is_numeric($value) ){
                        $values[] = [$detective_id, $db->escape($value), $time];
                    }
                }
            }

            if( count($values) < 15000 ){
                $db->massInsert(Config::table_detective_exclude_artists, array("detective_id","artist_id","last_update"), $values);
            }
            else{
                echo "Too many excludes artists!!";
                return false;
            }
        }

        echo $uuid;
    }

    public static function deleteDetective($uuid){
        $user_id = UserSession::getUserId();
        $db = SoundDetectiveDB::getInstance();

        // Only logged in users can create detectives
        if( !$user_id ){
            return false;
        }

        // Check if we have permission
        $db->where("uuid = ?", [$uuid]);
        $row = $db->ObjectBuilder()->getOne(Config::table_detective, ["id", "owner_id"]);

        if( $row ){
            if( $row->owner_id == $user_id ){
                $id = $row->id;

                $db->rawQuery("DELETE FROM " . Config::table_detective . " WHERE id = '$id'");
                $db->rawQuery("DELETE FROM " . Config::table_detective_artists . " WHERE detective_id = '$id'");
                $db->rawQuery("DELETE FROM " . Config::table_detective_exclude_artists . " WHERE detective_id = '$id'");
                $db->rawQuery("DELETE FROM " . Config::table_detective_exclude_songs . " WHERE detective_id = '$id'");
            }
        }
    }
    
    public static function massExcludeFromDetective($uuid, $songs){
        $user_id = UserSession::getUserId();
        $db = SoundDetectiveDB::getInstance();
        $time = time();
    
        // Only logged in users can create detectives
        if( !$user_id ){
            return false;
        }
    
        $db->where("uuid = ?", [$uuid]);
        $row = $db->ObjectBuilder()->getOne(Config::table_detective, ["id", "owner_id"]);
    
        if( $row && $row->owner_id == $user_id ){
            $songs = explode(",", $songs);
            $songsLength = count($songs);

            if( $songsLength > 0 && $songsLength <= 1000 ){
                $data = [];

                for($i=0;$i<$songsLength;$i++){
                    $data[] = [$row->id, $songs[$i], $time];
                }

                $db->massInsert(Config::table_detective_exclude_songs, ["detective_id", "song_id", "last_update"], $data);
            }
        }
    }

    public static function excludeFromDetective($uuid, $artist_id = NULL, $song_id = NULL){
        $user_id = UserSession::getUserId();
        $db = SoundDetectiveDB::getInstance();

        // Only logged in users can create detectives
        if( !$user_id ){
            return false;
        }

        $db->where("uuid = ?", [$uuid]);
        $row = $db->ObjectBuilder()->getOne(Config::table_detective, ["id", "owner_id"]);

        if( $row && $row->owner_id == $user_id ){
            if( $artist_id ){
                $db->insert(Config::table_detective_exclude_artists, array("detective_id" => $row->id,
                                                                                   "artist_id" => $artist_id,
                                                                                   "last_update" => time()));
            }
            else if( $song_id ){
                $db->insert(Config::table_detective_exclude_songs, array("detective_id" => $row->id,
                                                                                 "song_id" => $song_id,
                                                                                 "last_update" => time()));
            }
        }
    }

    public static function getCompleteCollection(){
        $user_id = UserSession::getUserId();
        $db = SoundDetectiveDB::getInstance();

        if( !$user_id ){
            return false;
        }

        $result = $db->ObjectBuilder()->rawQuery("SELECT MIN(sm.title) as title,MIN(sm.artists) as artist_name,MIN(s.artist_id) as artist_id,MIN(s.artist_id2) as artist_id2,MIN(s.artist_id3) as artist_id3,MIN(s.spotify_id) as spotify_id,sm.id as db_id,MIN(s.created) as created FROM " . Config::table_songs . " as sm INNER JOIN (SELECT p.song_id,p.artist_id,p.artist_id2,p.artist_id3,p.spotify_id,c.created FROM " . Config::table_tracks . " as p INNER JOIN (SELECT song_id,created FROM " . Config::table_user_collection . " WHERE user_id = '" . $user_id . "' ORDER BY created) as c ON p.song_id = c.song_id ) as s ON sm.id = s.song_id GROUP BY db_id ORDER BY s.created DESC");

        echo json_encode(Util::parseResultSet($result));
    }

    public static function changePassword($id, $key, $password){
        $db = SoundDetectiveDB::getInstance();

        $db->where("id = ?", [$id]);
        $row = $db->ObjectBuilder()->getOne(Config::table_user, ["id","reset_code","reset_valid_till"]);

        if( $row ){
            if( !$key || $row->reset_code != $key ){
                echo "Wrong reset key! Try to resend the reset E-Mail!";
            }
            else{
                if( intval($row->reset_valid_till) >= time()  ){
                        // Password Length
                        if( strlen($password) < 4 || strlen($password) > 32 ){
                            echo "Wrong Password length!";
                            return;
                        }

                        $db->where("id = ?", [$row->id]);
                        $db->update(Config::table_user, array("password" => md5($password),
                            "reset_code" => null,
                            "reset_valid_till" => null));
                }
                else{
                    echo "The reset key is expired! Please resend the reset E-Mail!";
                }
            }
        }
        else{
            echo "No user with this ID found!";
        }
    }

    public static function resetPassword($email){
        $privatekey = Config::recaptcha_private_key;

        $r = json_decode(HttpRequest::sendPostRequest("https://www.google.com/recaptcha/api/siteverify", array("secret" => $privatekey,
                                                                                           "response" => $_POST["g-recaptcha-response"],
                                                                                           "remoteip" => $_SERVER["REMOTE_ADDR"])), true);
        if (!$r || !$r["success"]) {
            // What happens when the CAPTCHA was entered incorrectly
            echo "Captcha was wrong!";
            return;
        }

        $db = SoundDetectiveDB::getInstance();

        $db->where("email = ?", [$email]);
        $row = $db->ObjectBuilder()->getOne(Config::table_user, ["id", "name"]);

        if( $row ){
            require_once './app/Thirdparty/PHPMailer/PHPMailerAutoload.php';

            $resetCode = Util::getRandomString(12);

            $subject = 'SoundDetective: Password Reset';

            $message = file_get_contents(dirname ( __FILE__ ) . "/../tmpl/forgot_password_email.tmpl.php");
            $message = preg_replace("/%%reset_link%%/", Config::playUrl."/#resetPassword/".$row->id."/".$resetCode, $message);
            $message = preg_replace("/%%user_name%%/", htmlspecialchars($row->name), $message);

            $db->where("id = ?", [$row->id]);
            $db->update(Config::table_user, array("reset_code" => $resetCode,
                "reset_valid_till" => time() + 43200));

            $mail = new \PHPMailer;

            $mail->isSMTP();
            $mail->Host = Config::email_host;
            $mail->SMTPAuth = true;
            $mail->Username = Config::email_username;
            $mail->Password = Config::email_password;
            $mail->SMTPSecure = 'tls';
            $mail->Port = 25;

            $mail->setFrom(Config::email_from, 'Sound Detective');
            $mail->addAddress($email);     // Add a recipient
            $mail->isHTML(true);                                  // Set email format to HTML

            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody  = "Hello! Someone requested a password reset for your account. If you would like to reset your password click on the following link: " . Config::playUrl . "/index.php?a=resetpassword&id=".$row->id."&key=".$resetCode;

            if(!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
            }
        }
        else{
            echo "No user with this E-Mail found!";
        }
    }

    public static function registerV2($username = NULL, $password = NULL, $email = NULL){
        // Check if captcha wrong
        $privatekey = Config::recaptcha_private_key;
        //$resp = recaptcha_check_answer ($privatekey,
        //                                $_SERVER["REMOTE_ADDR"],
        //                                $_POST["recaptcha_challenge_field"],
        //                                $_POST["recaptcha_response_field"]);

        $r = json_decode(HttpRequest::sendPostRequest("https://www.google.com/recaptcha/api/siteverify", array("secret" => $privatekey,
                                                                                           "response" => $_POST["g-recaptcha-response"],
                                                                                           "remoteip" => $_SERVER["REMOTE_ADDR"])), true);

        if (!$r || !$r["success"]) {
            // What happens when the CAPTCHA was entered incorrectly
            echo "Captcha was wrong!";
            return;
        }

        // Password Length
        if( strlen($password) < 4 || strlen($password) > 32 ){
            echo "Wrong Password length!";
            return;
        }

        // Username
        if( strlen($username) < 4 || strlen($username) > 18 || preg_match("/[^A-Za-z0-9\s]+/", $username) ){
            echo "Wrong Username format!";
            return;
        }

        // Email
        if( strlen($email) < 4 || strlen($email) > 128 || !preg_match('/^[^0-9]*[a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[@][a-zA-Z0-9_]+([.][a-zA-Z0-9_]+)*[.][a-zA-Z]{2,4}$/', $email) ){
            echo "Wrong E-Mail format!";
            return;
        }

        // check if username exists
        $db = SoundDetectiveDB::getInstance();

        $db->where("name = ? OR email = ?", [$username, $email]);
        $row = $db->ObjectBuilder()->getOne(Config::table_user, ["id", "name", "email"]);

        if( $row ){
            if( strtolower($row->name) === strtolower($username) ){
                echo "Username already in use!";
                return;
            }
            else{
                echo "E-Mail already in use!";
                return;
            }
        }

        // register
        UserSession::register($email, $username, md5($password));
    }

    public static function resetPasswordEmail($id, $key){
        global $page;
        global $errorText;
        global $reset_id;
        global $reset_key;

        $page = "RESET_PASSWORD";
        $errorText = "";

        $db = SoundDetectiveDB::getInstance();

        $db->where("id = ?", [$id]);
        $row = $db->ObjectBuilder()->getOne(Config::table_user, ["id","reset_code","reset_valid_till"]);

        if( $row ){
            if( $row->reset_code != $key ){
                $errorText = "Wrong reset key! Try to resend the reset E-Mail!";
            }
            else{
                if( intval($row->reset_valid_till) >= time()  ){
                    $reset_id = $row->id;
                    $reset_key = $row->reset_code;
                }
                else{
                    $errorText = "The reset key is expired! Please resend the reset E-Mail!";
                }
            }
        }
        else{
            $errorText = "No user with this ID found!";
        }
    }

    public static function refreshToken(){
        if( isset($_SESSION["refresh_token"]) && $_SESSION["refresh_token"] ){
            $r = json_decode(HttpRequest::sendPostRequest("https://accounts.spotify.com/api/token", array("grant_type" => "refresh_token",
                                                                                           "refresh_token" => $_SESSION["refresh_token"]), array("Authorization: Basic " . Config::spotify_refresh_token)), true);

            if($r["access_token"]){
                $_SESSION["access_token"] = $r["access_token"];
                echo $_SESSION["access_token"];
            }
        }
    }

    public static function artistAutoComplete($name){
        if( strlen($name) < 2 || strlen($name) > 128 ){
            echo "[]";
            return false;
        }

        $db = SoundDetectiveDB::getInstance();

        $db->where("name LIKE '" . $db->escape($name) . "%' AND popularity >= 10");
        $db->orderBy("popularity", "DESC");
        $result = $db->ObjectBuilder()->get(Config::table_artists, 10, ["id", "name", "image_small"]);

        $arr = array();

        foreach( $result as $row ){
            array_push($arr, array("id" => $row->id, "name" => $row->name, "image_small" => $row->image_small));
        }

        echo json_encode($arr);
    }

    public static function getArtistId($name){
        $db = SoundDetectiveDB::getInstance();

        $db->where("name = ?", [$name]);
        $db->orderBy("popularity", "DESC");
        $row = $db->ObjectBuilder()->getOne(Config::table_artists, ["id", "name"]);

        if( $row ){
            echo json_encode(array("id" => $row->id, "name" => $row->name));
        }
        else{
            echo json_encode(array());
        }
    }

    public static function getCharts($genre_id = null){
        $db = SoundDetectiveDB::getInstance();

        // get user id for dem checkz
        $user_id = UserSession::getUserId();

        if( $genre_id == null ){
            $genre_id = 0;
        }

        $query = "SELECT MIN(ac.song_id) as db_id,MIN(sp.title) as title,MIN(sp.artists) as artist_name,MIN(spp.spotify_id) as spotify_id,MIN(spp.artist_id) as artist_id,MIN(spp.artist_id2) as artist_id2,MIN(spp.artist_id3) as artist_id3 FROM " . Config::table_charts . " as ac INNER JOIN " . Config::table_songs . " as sp ON ac.song_id = sp.id INNER JOIN " . Config::table_tracks . " as spp ON ac.song_id = spp.song_id WHERE genre_id = '" . $db->escape($genre_id) . "' GROUP BY ac.song_id ORDER BY place ASC";


        $result = $db->ObjectBuilder()->rawQuery($query);

        echo json_encode(Util::parseResultSet($result));
    }

    public static function loveSong($song_id = null){
        try{
            $db = SoundDetectiveDB::getInstance();
            $user_id = UserSession::getUserId();

            if( !$user_id ){
                return false;
            }

            if( !$song_id ){
                return false;
            }

            $db->where("user_id = ? AND song_id = ?", [$user_id, $song_id]);
            $row = $db->ObjectBuilder()->getOne(Config::table_user_collection, ["id"]);

            if( !$row ){
                $db->insert(Config::table_user_collection, array("user_id" => $user_id,
                                                                    "song_id" => $song_id,
                                                                    "created" => time()));
            }

            return true;
        }
        catch(Exception $e){
            if( defined('debug') ){
                echo "Error: " . $e->getMessage();
            }

            return false;
        }
    }

    public static function removeFromCollection($song_id = null){
        $db = SoundDetectiveDB::getInstance();
        $user_id = UserSession::getUserId();

        if( !$user_id ){
            return false;
        }

        if( !$song_id ){
            return false;
        }

        $db->rawQuery("DELETE FROM " . Config::table_user_collection . " WHERE user_id = '" . $db->escape($user_id) . "' AND song_id = '" . $db->escape($song_id) . "'");

        return true;
    }

    public static function getYoutubeVideoId($song_id){
        $db = SoundDetectiveDB::getInstance();

        $db->where("id = ?", [$song_id]);
        $row = $db->ObjectBuilder()->getOne(Config::table_songs, ["title", "artists"]);

        if( $row ){
                if (!isset($_SESSION['country_code']))
                {
                    $_SESSION['country_code'] = Util::getLocation();
                }

                YouTube::searchForVideo(Util::transformSong(implode(" ", explode(";;", $row->artists)), $row->title), Util::transformSong(explode(";;", $row->artists)[0], $row->title), $song_id, $_SESSION['country_code']);
        }
    }

    public static function getVideoId($song_id){
        $db = SoundDetectiveDB::getInstance();

        $db->where("id = ?", [$song_id]);
        $row = $db->ObjectBuilder()->getOne(Config::table_songs, ["title", "artists"]);

        if( $row ){
                if (!isset($_SESSION['country_code']))
                {
                    $_SESSION['country_code'] = Util::getLocation();
                }

                YouTube::searchForVideo(Util::transformSong(implode(" ", explode(";;", $row->artists)), $row->title), Util::transformSong(explode(";;", $row->artists)[0], $row->title), $song_id, $_SESSION['country_code'], true);
        }
    }
}