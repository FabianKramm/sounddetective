<?php
namespace SoundDetective\Update;

use SoundDetective\Util\DatabaseStream;
use SoundDetective\Util\Log;
use SoundDetective\Util\Util;
use SoundDetective\Util\SoundDetectiveDB;

class RelationCalculation {
    /** @var SoundDetectiveUpdateDB */
    private $db;

    /** @var Log */
    private $log;

    private $edgeList = [];
    private $nodeList = [];

    private $edgeMatrix = [];

    private $currentArtistId;

    const amountUpdate = 10;
    const minArtistPopularity = 25;

    const updateCalculationAfter = 1004800;

    function __construct($connectToLiveDB = FALSE)
    {
        if( $connectToLiveDB ){
            $this->db = SoundDetectiveDB::getInstance();
        }
        else{
            $this->db = SoundDetectiveUpdateDB::getInstance();
        }

        $this->log = Log::getInstance();
    }

    private function weightImportance($importance){
        if ($importance < 6) {
            return 1;
        }
        else if ($importance < 11) {
            return 0.85;
        }
        else if ($importance < 16) {
            return 0.7;
        }
        else if ($importance < 21) {
            return 0.55;
        }
        else{
            return 0.0;
        }
    }

    private function addItems(&$array, array $add) {
        foreach ($add as $key => $value) {
            $array[$key] = $value;
        }
    }

    private function buildEdgeMatrix() {
        $this->edgeMatrix = [];

        foreach ($this->edgeList as $key => $value) {
            $nodes = explode(",", $key);

            $source = $nodes[0];
            $target = $nodes[1];

            if ( $this->nodeList[$source]["pop"] < 10 || $this->nodeList[$target]["pop"] < 10 ){
                continue;
            }

            if ( !isset($this->edgeMatrix[$source]) ){
                $this->edgeMatrix[$source] = [
                    $target => [$key]
                ];
            }
            else {
                if ( isset($this->edgeMatrix[$source][$target]) ){
                    $this->edgeMatrix[$source][$target][] = $key;
                }
                else{
                    $this->edgeMatrix[$source][$target] = [$key];
                }
            }

            if ( !isset($this->edgeMatrix[$target]) ){
                $this->edgeMatrix[$target] = [
                    $source => [$key]
                ];
            }
            else{
                if ( isset($this->edgeMatrix[$target][$source]) ){
                    $this->edgeMatrix[$target][$source][] = $key;
                }
                else{
                    $this->edgeMatrix[$target][$source] = [$key];
                }
            }
        }
    }

    private function fetchRelatedArtists($depth = 3) {
        $weightAdjustment = [ 1.0, 1.0, 1.0 ];

        $doneList = [];
        $searchList = [
            $this->currentArtistId => 1
        ];

        for($i=0;$i<$depth;$i++) {
            if ( count($searchList) == 0 ) {
                break;
            }

            $searchString = join(",", array_keys($searchList));
            $result = $this->db->completeRawQuery("SELECT from_id,to_id,importance FROM ".Config::table_update_related." WHERE from_id IN (" . $searchString . ") OR to_id IN (" . $searchString . ")");
            //$this->log->write("Current Depth: " . ($i+1) . " SearchList: " . count($searchList));

            $this->addItems($doneList, $searchList);
            $searchList = [];

            /* fetch object array */
            while ($obj = $result->fetch_object()) {
                $from_id = strval($obj->from_id);
                $to_id = strval($obj->to_id);
                $importance = floatval($obj->importance);

                if( !isset($this->nodeList[$from_id]) ) {
                    $this->nodeList[$from_id] = 1;
                }

                if( !isset($this->nodeList[$to_id]) ) {
                    $this->nodeList[$to_id] = 1;
                }

                if( !isset($this->edgeList[$from_id . "," . $to_id]) ){
                    $this->edgeList[$from_id . "," . $to_id] = $this->weightImportance($importance);
                    //$this->edgeList[$from_id . "," . $to_id] = $importance;
                }

                if( !isset($doneList[$from_id]) && !isset($searchList[$from_id]) ){
                    $searchList[$from_id] = 1;
                }
                if( !isset($doneList[$to_id]) && !isset($searchList[$to_id]) ){
                    $searchList[$to_id] = 1;
                }
            }

            $result->close();
        }
    }

    private function getNumberOfEdgesBetween($nodes) {
        $amount = 0;
        $doneEdges = [];

        foreach ($nodes as $node => $val){
            if( isset($this->edgeMatrix[$node]) ){
                foreach ($this->edgeMatrix[$node] as $key => $edges) {
                    if( !isset($nodes[$key]) ){
                        continue;
                    }

                    if( !isset($doneEdges[$key.",".$node]) ){
                        $doneEdges[$node.",".$key] = 1;
                        $amount += count($edges);
                    }
                }
            }
        }

        return $amount;
    }

    private function degree($artistId) {
        if( !isset($this->edgeMatrix[$artistId]) ){
            return 0;
        }

        $degree = 0;

        foreach ($this->edgeMatrix[$artistId] as $target => $edges) {
            $degree += count($edges);
        }

        return $degree;
    }

    public function insertCalculatedRelated($useMutexStream = TRUE) {
        $this->log->write("Task started!");
        $time = time();

        if( $useMutexStream ){
            $that = $this;
            $mutexStream = new MutexStream("insertCalculatedRelated", function () use (&$that){
                $time = time();

                $that->db->where ("(last_update_related_calculated = '0' OR last_update_related_calculated <= ?) AND popularity >= ?", [$time - RelationCalculation::updateCalculationAfter, RelationCalculation::minArtistPopularity]);

                return $that->db->ArrayBuilder()->get(Config::table_update_artists, 500, ["id"]);
            });

            try{
                usleep(rand(1000,100000));

                $mutexStream->lock();
                $artists = $mutexStream->getData(RelationCalculation::amountUpdate, false);
            }
            catch(MutexIsLockedException $e){
                $this->log->write("Error: Mutex locked!");
                return;
            }
        }
        else{
            $this->db->where ("(last_update_related_calculated = '0' OR last_update_related_calculated <= ?) AND popularity >= ?", [$time - self::updateCalculationAfter, self::minArtistPopularity]);
            $artists = $this->db->ArrayBuilder()->get(Config::table_update_artists, self::amountUpdate, ["id"]);
        }

        $updateArtists = new DatabaseStream(Config::table_update_artists, ["id","last_update_related_calculated"], DatabaseStream::UPDATE, ["spotify_id","name","popularity","image","image_background","image_small","last_update","last_update_top","last_update_related"]);
        $deleteRelated = new DatabaseStream(Config::table_update_related_calculated, "from_id", DatabaseStream::DELETE);

        foreach ($artists as $artist){
            $updateArtists->add([$artist["id"],$time]);
            $deleteRelated->add($artist["id"]);
        }

        $updateArtists->commit();
        $deleteRelated->commit();

        if($useMutexStream){
            $mutexStream->unlock();
        }

        $insertTracks = new DatabaseStream(Config::table_update_related_calculated, ["from_id", "to_id", "difference"]);

        foreach ($artists as $artist){
            // 872
            $this->calculateRelated($artist["id"], $insertTracks);
        }

        $insertTracks->commit();
    }
    
    private function cosinusSimilarity($artist1, $artist2) {
        $sum = 0;

        if( isset($this->edgeMatrix[$artist1]) && isset($this->edgeMatrix[$artist2]) ){
            foreach ($this->edgeMatrix[$artist1] as $key => $value) {
                if ( isset($this->edgeMatrix[$artist2][$key]) || $key == $artist2 ) {
                    if( count($this->edgeMatrix[$artist1][$key]) > 1 ) {
                        $edge1 = $this->edgeList[$this->edgeMatrix[$artist1][$key][0]];
                        $edge2 = $this->edgeList[$this->edgeMatrix[$artist1][$key][1]];

                        $artist1Val = sqrt($edge1 * $edge1 + $edge2 * $edge2);
                    }
                    else{
                        $artist1Val = $this->edgeList[$this->edgeMatrix[$artist1][$key][0]];
                    }

                    if( $key == $artist2 ){
                        $key = $artist1;
                    }

                    if( count($this->edgeMatrix[$artist2][$key]) > 1 ) {
                        $edge1 = $this->edgeList[$this->edgeMatrix[$artist2][$key][0]];
                        $edge2 = $this->edgeList[$this->edgeMatrix[$artist2][$key][1]];

                        $artist2Val = sqrt($edge1 * $edge1 + $edge2 * $edge2);
                    }
                    else{
                        $artist2Val = $this->edgeList[$this->edgeMatrix[$artist2][$key][0]];
                    }

                    $sum += $artist1Val * $artist2Val;
                }
            }

            if( $sum == 0 ){
                //echo $this->nodeList[$artist1]["name"] . " - " . $this->nodeList[$artist2]["name"] . ": " . 0.0 . "<br>";

                return 0.0;
            }
            else{
                //$penalty = $am / min(count($this->edgeMatrix[$artist1]), count($this->edgeMatrix[$artist2]));
                //return ($sum / ($a * $b)) * $penalty;
                $result = $sum / (min(count($this->edgeMatrix[$artist1]), count($this->edgeMatrix[$artist2])) * 0.775);

                //echo $this->nodeList[$artist1]["name"] . " - " . $this->nodeList[$artist2]["name"] . ": " . $result . "<br>";

                return $result;
            }
        }

        return 0.0;
    }

    /**
     * @param $artistId
     * @param DatabaseStream $stream
     */
    public function calculateRelated($artistId, DatabaseStream &$stream = NULL) {
        $this->log->write("Start with artist $artistId");

        $this->currentArtistId = strval($artistId);
        $this->nodeList = [
            $this->currentArtistId => 1
        ];
        $this->edgeList = [];

        $this->fetchRelatedArtists();

        // Consts
        $nodeAmount = count($this->nodeList);

        $this->log->write("Done fetching related artists ($nodeAmount)...");

        if( $nodeAmount == 1 && $stream !== null ){
            $stream->add([$this->currentArtistId, $this->currentArtistId, '0']);

            return;
        }

        if( $stream !== null ){
            $result = $this->db->completeRawQuery("SELECT id,popularity FROM ".Config::table_update_artists." WHERE id IN (" . join(",", array_keys($this->nodeList)) . ")");
            while ($obj = $result->fetch_object()) {

                $this->nodeList[strval($obj->id)] = [
                    'id' => strval($obj->id),
                    'pop' => intval($obj->popularity)
                ];
            }
        }
        else{
            $result = $this->db->completeRawQuery("SELECT id,name,popularity FROM ".Config::table_update_artists." WHERE id IN (" . join(",", array_keys($this->nodeList)) . ")");
            while ($obj = $result->fetch_object()) {

                $this->nodeList[strval($obj->id)] = [
                    'id' => strval($obj->id),
                    'name' => $obj->name,
                    'pop' => intval($obj->popularity)
                ];
            }
        }

        $result->close();
        $this->buildEdgeMatrix();

        $this->log->write("Done Building Matrix...");

        if ( count($this->edgeMatrix) == 0 || !isset($this->edgeMatrix[$this->currentArtistId]) ) {
            if( $stream !== null ){
                $stream->add([$this->currentArtistId, $this->currentArtistId, '0']);
            }

            return;
        }

        $vSearch = [
            $this->currentArtistId => 1
        ];

        $vDegree = floatval($this->degree($this->currentArtistId));

        $distances = [];
        $stopValue = min(0.15, max(0.040, 0.13 - (0.000013 * $nodeAmount)));

        $popularityPenelaty = min(20, $nodeAmount/50.0);

        $currentId = $this->currentArtistId;

        for ($i=0;$i<round(count($this->edgeMatrix)/4.0);$i++) {
            foreach ($this->edgeMatrix[$currentId] as $x => $value) {
                if ( !isset($vSearch[$x]) ) {
                    $popularityModifier = min(0.85, count($vSearch)/$popularityPenelaty) * max(0.3, (1.0-($this->nodeList[$x]["pop"]/100.0))) * (1.0 - min(0.7, pow($this->degree($x)/$vDegree, 1)));
                    $distanceModifier = min(1.0, (1.0/log($vSearch[$currentId],1.3)));

                    $cosineSim = $this->cosinusSimilarity($currentId, $x);
                    $calc = $cosineSim * $distanceModifier + $popularityModifier + 0.05;

                    if( !isset($distances[$x]) ){
                        $distances[$x] = 0;
                    }

                    if( count($this->edgeMatrix[$currentId][$x]) > 1 ){
                        $calc2 = $calc;

                        $distances[$x] += $calc + $calc2;
                    }
                    else{
                        if ( isset($this->edgeList[$x . "," . $currentId]) ){
                            $distances[$x] += $calc * 0.75;
                        }
                        else{
                            $distances[$x] += $calc;
                        }
                    }
                }
            }

            $currentId = -1;
            $currentIdAmount = -1;

            foreach ($distances as $x => $val) {
                $amount = $val;

                if( $amount > $currentIdAmount ){
                    $currentIdAmount = $amount;
                    $currentId = $x;
                }
            }

            if ( $currentId == -1 ){
                break;
            }

            unset($distances[$currentId]);

            $vSearch[$currentId] = count($vSearch) + 1;
            $outputCount = count($vSearch);

            if( $i % 15 == 0 && (2.0 * $this->getNumberOfEdgesBetween($vSearch)/($outputCount*($outputCount-1))) < $stopValue ){
                break;
            }

			if( $outputCount == $nodeAmount ){
                break;
            }
        }

        $this->log->write("Done calculating related artists (".count($vSearch).")...");

        if( $stream !== null ) {
            $nodeAmount = count($vSearch) * 2.0;

            foreach ($vSearch as $artist => $val) {
                $stream->add([$this->currentArtistId, $artist, (($val-1) / $nodeAmount)]);
            }
        }
        else{
            $str = [];

            //ksort($vSearch);

            foreach ($vSearch as $artist => $val) {
                $str[] = $this->nodeList[$artist]["name"];
            }

            $this->log->write(implode(", ", $str));
        }
    }

    private function fetchRecommendedArtists($artist_ids, $depth = 2) {
        $doneList = [];
        $searchList = [];

        foreach ($artist_ids as $key => $value){
            $searchList[$key] = 1;
            $this->edgeList["0" . "," . $key] = $value;
            $this->nodeList[$key] = 1;
        }

        for($i=0;$i<$depth;$i++) {
            if ( count($searchList) == 0 ) {
                break;
            }

            $searchString = join(",", array_keys($searchList));
            $result = $this->db->completeRawQuery("SELECT from_id,to_id,importance FROM ".Config::table_update_related." WHERE from_id IN (" . $searchString . ") OR to_id IN (" . $searchString . ")");
            //$this->log->write("Current Depth: " . ($i+1) . " SearchList: " . count($searchList));

            $this->addItems($doneList, $searchList);
            $searchList = [];

            /* fetch object array */
            while ($obj = $result->fetch_object()) {
                $from_id = strval($obj->from_id);
                $to_id = strval($obj->to_id);
                $importance = floatval($obj->importance);

                if( !isset($this->nodeList[$from_id]) ) {
                    $this->nodeList[$from_id] = 1;
                }

                if( !isset($this->nodeList[$to_id]) ) {
                    $this->nodeList[$to_id] = 1;
                }

                if( !isset($this->edgeList[$from_id . "," . $to_id]) ){
                    $this->edgeList[$from_id . "," . $to_id] = $this->weightImportance($importance);
                    //$this->edgeList[$from_id . "," . $to_id] = $importance;
                }

                if( !isset($doneList[$from_id]) && !isset($searchList[$from_id]) ){
                    $searchList[$from_id] = 1;
                }
                if( !isset($doneList[$to_id]) && !isset($searchList[$to_id]) ){
                    $searchList[$to_id] = 1;
                }
            }

            $result->close();
        }
    }

    public function calculateRecommendedArtists($userId) {
        $this->log->write("Start for user $userId");

        $result = $this->db->completeRawQuery("SELECT MIN(artist_id) as artist_id FROM user_collection as u INNER JOIN tracks as t ON u.song_id = t.song_id WHERE u.user_id = '$userId' GROUP BY t.song_id ORDER BY u.created DESC LIMIT 100");
        $artist_ids = [];

        while ($obj = $result->fetch_object()) {
            if( !isset($artist_ids[strval($obj->artist_id)]) ){
                $artist_ids[strval($obj->artist_id)] = 1;
            }
            else{
                $artist_ids[strval($obj->artist_id)] += 1;
            }

            if( count($artist_ids) > 60 ){
                break;
            }
            /*if( $obj->artist_id2 != FALSE && !isset($artist_ids[strval($obj->artist_id2)]) ){
                $artist_ids[strval($obj->artist_id2)] = 0.5;
            }
            else if ( $obj->artist_id2 != FALSE ) {
                $artist_ids[strval($obj->artist_id2)] += 0.5;
            }

            if( $obj->artist_id3 != FALSE && !isset($artist_ids[strval($obj->artist_id3)]) ){
                $artist_ids[strval($obj->artist_id3)] = 0.5;
            }
            else if ( $obj->artist_id3 != FALSE ) {
                $artist_ids[strval($obj->artist_id3)] += 0.5;
            }*/
        }

        $highest = 0;

        foreach ($artist_ids as $key => $value) {
            if( $value >= $highest ){
                $highest = $value;
            }
        }

        $count = count($artist_ids);
        $i=0;

        foreach ($artist_ids as $key => $value) {
            $artist_ids[$key] = (1-$i/$count) + ($value / $highest);
            $i++;
        }

        print_r($artist_ids);

        $this->currentArtistId = "0";
        $this->nodeList = [
            $this->currentArtistId => [
                'id' => "0",
                'name' => "User",
                'pop' => 100
            ]
        ];
        $this->edgeList = [];

        $this->fetchRecommendedArtists($artist_ids);

        // Consts
        $nodeAmount = count($this->nodeList);

        $this->log->write("Done fetching related artists ($nodeAmount)...");

        $result = $this->db->completeRawQuery("SELECT id,name,popularity FROM ".Config::table_update_artists." WHERE id IN (" . join(",", array_keys($this->nodeList)) . ")");
        while ($obj = $result->fetch_object()) {

            $this->nodeList[strval($obj->id)] = [
                'id' => strval($obj->id),
                'name' => $obj->name,
                'pop' => intval($obj->popularity)
            ];
        }

        $result->close();
        $this->buildEdgeMatrix();

        $this->log->write("Done Building Matrix...");

        if ( count($this->edgeMatrix) == 0 || !isset($this->edgeMatrix[$this->currentArtistId]) ) {
            return;
        }

        $vSearch = [
            $this->currentArtistId => 1
        ];

        $vDegree = floatval($this->degree($this->currentArtistId));

        $distances = [];
        $stopValue = min(0.15, max(0.040, 0.13 - (0.000013 * $nodeAmount)));

        $popularityPenelaty = min(30, $nodeAmount/60.0);

        $currentId = $this->currentArtistId;

        for ($i=0;$i<round(count($this->edgeMatrix)/2.0);$i++) {
            foreach ($this->edgeMatrix[$currentId] as $x => $value) {
                if ( !isset($vSearch[$x]) ) {
                    $popularityModifier = min(0.85, count($vSearch)/$popularityPenelaty) * max(0.3, (1.0-($this->nodeList[$x]["pop"]/100.0))) * (1.0 - min(0.7, pow($this->degree($x)/$vDegree, 1)));
                    $distanceModifier = min(1.0, (1.0/log($vSearch[$currentId],2)));

                    if ( count($vSearch) == 1 ){
                        $cosineSim = $this->edgeList[$value[0]];//$this->cosinusSimilarity($currentId, $x);
                    }
                    else{
                        $cosineSim = $this->cosinusSimilarity($currentId, $x);
                    }

                    $calc = $cosineSim * $distanceModifier + $popularityModifier + 0.05;

                    if( !isset($distances[$x]) ){
                        $distances[$x] = 0;
                    }

                    if( count($this->edgeMatrix[$currentId][$x]) > 1 ){
                        $calc2 = $calc;

                        $distances[$x] += $calc + $calc2;
                    }
                    else{
                        if ( isset($this->edgeList[$x . "," . $currentId]) ){
                            $distances[$x] += $calc * 0.75;
                        }
                        else{
                            $distances[$x] += $calc;
                        }
                    }
                }
            }

            if( count($vSearch) == 1 ){
                print_r($distances);
            }

            $currentId = -1;
            $currentIdAmount = -1;

            foreach ($distances as $x => $val) {
                $amount = $val;

                if( $amount > $currentIdAmount ){
                    $currentIdAmount = $amount;
                    $currentId = $x;
                }
            }

            if ( $currentId == -1 ){
                break;
            }

            unset($distances[$currentId]);

            $vSearch[$currentId] = count($vSearch) + 1;
            $outputCount = count($vSearch);

            if( $i % 15 == 0 && (2.0 * $this->getNumberOfEdgesBetween($vSearch)/($outputCount*($outputCount-1))) < $stopValue ){
                break;
            }

            if( $outputCount == $nodeAmount ){
                break;
            }
        }

        $this->log->write("Done calculating related artists (".count($vSearch).")...");
        $str = [];

        foreach ($vSearch as $artist => $val) {
            $str[] = $this->nodeList[$artist]["name"];
        }

        $this->log->write(implode(", ", $str));
    }
}