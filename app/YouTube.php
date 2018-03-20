<?php
namespace SoundDetective;

use SoundDetective\Util\HttpRequest;
use SoundDetective\Util\Util;
use SoundDetective\Util\Config;
use SoundDetective\Util\SoundDetectiveDB;
use DateInterval;

class YouTube{
    private static $YOUTUBEAPIKEY = Config::youtube_api_key;
    //  &regionCode=DE
    private static $YTSEARCHURL = "https://www.googleapis.com/youtube/v3/search?part=id%2Csnippet&key=" . Config::youtube_api_key . "&type=video&maxResults=8&q=";
    private static $YTDETAILSURL = "https://www.googleapis.com/youtube/v3/videos?part=contentDetails%2Cstatistics%2Cstatus&key=" . Config::youtube_api_key . "&id=";

    // refetch when older than 2 weeks
    private static $refetch = 604800;

    private static $videos = [];

    private static function returnDateTimeInterval($string){
            $a = new DateInterval($string);

            return $a->s + $a->i * 60 + $a->h * 3600 + $a->d * 86400;
    }

    private static function checkStringForExcluded($string, $stringToCompare){
        $titleExcludes = array("speed",
                               "cappella",
                               "motivation",
                               "schnelle",
                               "schneller",
                               "manchester",
                                "opening",
                                "choir",
                                "about",
                                "reads",
                                "read",
                                "fast version",
                                "chipettes version",
                                "vocal music video",
                                "track by track",
                                "zumba version",
                                "opens up about",
                                "got talent",
                                "set",
                                "funny",
                                "speed up",
                                "parodia",
                                "live",
                                "vocalcover",
                                "story",
                                "stories",
                                "highlight",
                                "highlights",
                                "the voice",
                                "made",
                                "show",
                                "bbc",
                                "barcelona",
                                "restrung",
                                "en",
                                "fitness",
                                "warmup",
                                "choreography",
                                "shows",
                                "talk",
                                "talks",
                                "talked",
                                "explain",
                                "explains",
                                "explained",
                                "lullaby",
                                "reaction",
                                "react",
                                "reacting",
                                "session",
                                "sessions",
                                "hotel",
                                "cafe",
                                "making",
                                "mtv",
                                "freestyle",
                                "preview",
                                "previews",
                                "previewed",
                                "snippet",
                                "piano",
                                "cover",
                                "covers",
                                "covered",
                                "remake",
                                "instrumental",
                                "intrumental",
                                "instrummental",
                                "instrumentals",
                                "tribute",
                                "review",
                                "trailer",
                                "tutorial",
                                "interview",
                                "comentarios",
                                "commentary",
                                "comment",
                                "comments",
                                "kommentar",
                                "kommentare",
                                "meinung",
                                "opinion",
                                "karaoke",
                                "lesson",
                                "minions",
                                "chipmunks",
                                "chipmunk",
                                "anniversary",
                                "sung",
                                "mlp",
                                "nightcore",
                                "nightstep",
                                "n i g h t c o r e",
                                "behind the scenes",
                                "rendition",
                                "festival",
                                "world tour",
                                "iphone",
                                "android",
                                "at",
                                "acapella",
                                "perform",
                                "performs",
                                "performed",
                                "performing",
                                //"vs",
                                "tour",
                                "acoustic",
                                "accoustic",
                                "acustico",
                                "parody",
                                "parodie",
                                "concierto",
                                "concert",
                                "reversed",
                                "remixed by",
                                "sydney",
                                "brazil",
                                "brasil",
                                "puerto rico",
                                "atlanta",
                                "liverpool",
                                "amsterdam",
                                "la",
                                "audio",        // meistens sind das nur konzerte
                                "new glasgow",
                                "los angeles",
                                "california",
                                "miami",
                                "montreal",
                                "chicago",
                                "vine",
                                "compilation",
                                "fallon",
                                "kimmel",
                                "letterman",
                                "airport",
                                "presentando",
                                "moscow",
                                "nashville",
                                "guitar",
                                "vienna",
                                "phoenix",
                                "first impression",
                                "event",
                                "audition",
                                "x factor",
                                "isolated vocals",
                                "radio 1",
                                "mashup",
                                "mash",
                                "xfactor",
                                "australia",
                                "vocal rock",
                                "rock version",
                                "dubstep version",
                                "university",
                                "school",
                                "southampton",
                                "summertime ball",
                                "male version",
                                "female version",
                                "berlin",
                                "bonus",
                                "episode",
                                "dbtv",
                                "raleigh",
                                "unrecorded song",
                                "improvisation",
                                "singing",
                                "sings",
                                "sing",
                                "announces",
                                "performance",
                                "surprises",
                                "camden",
                                "bing lounge",
                                "gruene hall",
                                "how to",
                                "teen choice awards",
                                "on his",
                                "on their",
                                "on her",
                                "talking",
                                "giveaway",
                                "nighttrap",
                                "bit",
                                "fan",
                                "madrid",
                                "rio",
                                "bangkok",
                                "telehit",
                                "james corden",
                                //"with",
                                "flashmob",
                                "argentina",
                                "birthday",
                                "extravaganza",
                                "nyc",
                                "has",
                                "have",
                                "had",
                                "auf deutsch",
                                "15",
                                "14",
                                "13",
                                "12",
                                "11",
                                "10",
                                "09",
                                "08",
                                "07",
                                "06",
                                "05",
                                "04",
                                "03",
                                "02",
                                "01",
                                "99",
                                "98",
                                "97",
                                "96",
                                "95",
                                "94",
                                "93",
                                "92",
                                "91",
                                "90",
                                "as",
                                "widescreen",
                                "rapidement",
                                "fashion",
                                "chimpunk",
                                //"boosted",
                                "boost",
                                "naked",
                                "secret",
                                "sobs",
                                "refix",
                                "bookish song",
                                "reply",
                                "fetus",
                                "oakland",
                                "arizona",
                                "season",
                                "drums by",
                                "parodi",
                                "by",
                                "idol",
                                "petody",
                                "costume",
                                "awards",
                                "award",
                                "sped up",
                                "designing",
                                "lego",
                                "backwards",
                                "grammy",
                                "emotional",
                                "colaborativo",
                                "today",
                                "makeup",
                                "drum",
                                "snl",
                                "handbag",
                                "alikaislamadina",
                                "superfruit",
                                "in",
                                "traducida al");

        $newExcludeString = [];

        foreach ($titleExcludes as $_value) {
            if( !preg_match("/(^|[^A-Za-z])(" . $_value . ")([^A-Za-z]|$)/", $string) ){
                $newExcludeString[] = $_value;
            }
        }

        $titleExcludes = $newExcludeString;

         /*
         * Suchen wir einen Remix?
         */
        if( strpos($string, "remix") !== FALSE ){
            $string = preg_replace('/remix/', ' ', $string);
        }
        else{
            $titleExcludes[] = "remix";
            $titleExcludes[] = "bootleg";
            //$titleExcludes[] = "edit";
            $titleExcludes[] = "rework";
            $titleExcludes[] = "rmx";
        }

        $matches = null;

        // exclude bad ones
        if( preg_match("/(^|[^A-Za-z0-9])(" . implode("|", $titleExcludes) . ")([^A-Za-z0-9]|$)/", $stringToCompare, $matches) || strpos($stringToCompare, "@") !== FALSE ){
            if ( defined("debug") && $matches ){
                echo "Out because of: " . $matches[0] . "<br>";
            }

            return false;
        }

        return true;
    }

    private static function checkTitle($videoTitle, $name, $i = 0, $description = "", $channelTitle = "", $nameWithAllArtists = NULL){
        $videoTitle = strtolower(Util::remove_accents($videoTitle));

        if( !$description ){
            $description = "";
        }
        $description = strtolower(Util::remove_accents($description));

        if( defined("debug") ){
            echo "<br>Title: " . $videoTitle . "<br>";
            echo "Name: " . $name . "<br>";
            echo "Description: " . $description . "<br>";
        }

        $value = 0;

        if( $nameWithAllArtists ){
            if( !self::checkStringForExcluded($nameWithAllArtists, $videoTitle) ){
                return false;
            }
        }
        else{
            if( !self::checkStringForExcluded($name, $videoTitle) ){
                return false;
            }
        }

        /*
         * Suchen wir einen Remix?
         */
        if( strpos($name, "remix") !== FALSE ){
            $positive = array("remix" => 1.5, "lyrics" => 0.1, "lyric" => 0.1);
        }
        else{
            $positive = array("official" => 2, "original" => 1, "lyrics" => 0.09, "lyric" => 0.09);
        }

        // check channels
        $blacklistChannels = array("UCJOl_M0fs1TbfEvoy2MXLDQ",
                                   "UCJ-Ociya7ri8MKx1O5jCIKQ",
                                   "UCPPmb-zmMQskPpQXb-Ev79A",
                                   "UCS80zZBbJPpim2nbevyP_tA",
                                   "UCNpY7BgxICtDNZDSGtqUdAw",
                                   "UCdtXPiqI2cLorKaPrfpKc4g",
                                   "UCdYkHGMFYBy6rlFk5TO0Xxg",
                                   "UC8LnPjOiLAvtp8u0Q_kYXfA",
                                   "UCYKqJg_eaSwAO_nHzu4r5lA",
                                   "UCgITW_70LNZFkNna7VsXbuQ",
                                   "UCdwyuxVwFchhZ4xtFRvT5EA",
                                   "UCCrcEKyqnPtY-BgSYDiRb0g",
                                   "UCgc00bfF_PvO_2AvqJZHXFg",
                                   "UC_ZTmAt4G3AotY5RtfDK3Pg",
                                   "UCdO1g2bYAFHLRHKdbrrnctQ",
                                   "UCbXShdwa9CBHDlDkL16D6XQ",
                                   "UCZH4EVUqljV-zkZEd0Ot7EQ",
                                   "UCxl2BGCsmu3lx7Fzchih2aQ",
                                   "UCWljxewHlJE3M7U_6_zFNyA",
                                   "UC7FhOuPmxz8spz_xvJf_xow",
                                   "UCPIvT-zcQl2H0vabdXJGcpg",
                                   "UCyjuFsbclXyntSRMBAILzbw",
                                   "UCl_gJnTge9bcRDxg2iCT3vQ",
                                   "UCpnFs369c7T-yXu91YqIetQ",
                                   "UCaHE2Xd6bhJbfM7T1TAmI9Q",
                                   "UCmUmhpHyMHdwZSLZigDTDOw",
                                   "UC36MbqcOFOYUn4OeaKs_HpA",
                                   "UCoRTjHb-5MCIKggvH8C52Mw",
                                   "UC94Z4HZJkhPm94YPH1GE3bw",
                                   "UCH3o9GfRGjZHrRUT0MaY7gg",
                                   "UCQjh-JVPNWfY-KsZS3RgRHw",
                                   "UCzH549YlZhdhIqhtvz7XHmQ",
                                   "UChm1iwGFSWC-ZWRaQAVp_yQ",
                                   "UCey_c7U86mJGz1VJWH5CYPA",
                                   "UCzl6jYOaGJzx_C2wsVhDOCQ",
                                   "UCpeRPCd8AZ3tyEbzZwoKseQ",
                                   "UCUDQixiTZQXkad5_SAWQA2Q",
                                   "UCLeMVph8z3bIhFCTB-8q57w",
                                   "UCQdAedEtx88fxD86GDBSQQg",
                                   "UCix7LtiKgO5JQgmU3nG0qgg",
                                   "UCjWRi2qaGtKjQyoQLc4OGkw",
                                   "UCCDz_XYeKWd0OIyjp95dqyQ",
                                   "UCpxuU8Cb3U9A_lRQ_yrjdGw",
                                   "UCpJYKiSaM8GvGGUok2tddaA",
                                   "UCLRpI5yd10aJxSel3e6MlNw",
                                   "UCeIUN9eI9jR1ZljYTqK0vSw",
                                   "UC8-Th83bH_thdKZDJCrn88g",
                                   "UC7S1DWSgNf2zM0jgmDRh38w",
                                   "UCZFNnOm6n2grSKg2saca1Yw",
                                   "UCjwntn8siZL2uE43Y1pviqw",
                                   "UCon6xyGPvVSZpasy-85U8EA",
                                   "UC6npVUEGgMgfNZ0AJCmF68A");

        foreach ($blacklistChannels as $_value) {
            if( $_value == $channelTitle ){
                if( defined("debug") ){
                    echo "Out because of channel: " . $_value . "<br>";
                }

                return false;
            }
        }

        // check description
        $descriptionExclude = array("song was made by me",
                                    "manually created",
                                    "my walkthrough",
                                    "behind the scenes",
                                    "sped up",
                                    "karaoke",
                                    "me singing",
                                    "me performing",
                                    //"performing",
                                    "parody",
                                    "i made",
                                    "arena",
                                    "get to know",
                                    "performed",
                                    //"performance",
                                    "performs as",
                                    "fun video",
                                    "speed version",
                                    "chipmunk",
                                    "my version",
                                    "nightcore",
                                    "audio is bad",
                                    "cover",
                                    "covers",
                                    "acoustic version",
                                    "tutorial",
                                    "piano",
                                    "audio modificado",
                                    "changed audio",
                                    "interview",
                                    "interviews",
                                    "q\&a",
                                    "for the tonight show audience",
                                    "jimmy fallon",
                                    "live performance",
                                    "music awards",
                                    "this is stockholm",
                                    "anaheim convention center",
                                    "live at",
                                    "live room",
                                    "mini edit",
                                    "performs the song");
                                    //performs
                                    //performed
        $newDescExclude = [];

        foreach ($descriptionExclude as $_value) {
            if( !preg_match("/(^|[^A-Za-z])(" . $_value . ")([^A-Za-z]|$)/", $name) ){
                $newDescExclude[] = $_value;
            }
        }

        $matches = null;

        if( preg_match("/(^|[^A-Za-z])(" . implode("|", $newDescExclude) . ")([^A-Za-z]|$)/", $description, $matches) ){
            if( defined("debug") && $matches ){
                echo "Bad Description: " . $matches[0] . "<br>";
            }

            return false;
        }

        //$name = Util::eraseBracketsAndSpecialChars($name);

        foreach ($positive as $key => $_value) {
            if( preg_match("/(^|[^A-Za-z])" . $key . "([^A-Za-z]|$)/", $videoTitle) ){
                $value += $_value;
            }
        }

        // FORMAT NAME
        $name = preg_replace("/[^a-z](and|the|feat)[^a-z]/", " ", $name);
        $videoTitle = preg_replace("/\s/", "", $videoTitle);

        $split = explode(" ", $name);

        $words = 0;
        $found = 0;

        foreach ($split as $key => $_value) {
            if( strlen($_value) >= 2 ){
                $words++;

                if( strpos($videoTitle, $_value) !== FALSE ){
                    $found++;
                }
            }
        }

        if(defined("debug")){
            echo "Word Check: " . $found . "/" . $words . " - " . floatval($found/$words) . "<br><br>";
        }

        if( floatval($found/$words) > 0.85 ){
            $value += (floatval($found/$words) - 0.85) * 6;
            $value += 1-($i/10);

            return $value;
        }

        return false;
    }

    public static function validateVideo($video_id){
        $result = HttpRequest::sendGetRequest(self::$YTDETAILSURL . urlencode($video_id));
        $r = json_decode($result, true);
        $like_ratio = 0;

        if( $r["items"] && count($r["items"]) == 1 ){
            // Check if embeddable
            if( !$r["items"][0]["status"]["embeddable"] ){
                return false;
            }

            // Duration validation
            $duration = self::returnDateTimeInterval($r["items"][0]["contentDetails"]["duration"]);

            if( $duration < 120 || $duration > 600 ){
                if( defined("debug") ){
                    echo "Zu lang/zu kurz!<br>";
                }

                return false;
            }

            if( intval($r["items"][0]["statistics"]["dislikeCount"]) > 0 ){
                $like_ratio = intval($r["items"][0]["statistics"]["likeCount"])/intval($r["items"][0]["statistics"]["dislikeCount"]);
            }
            else{
                $like_ratio = intval($r["items"][0]["statistics"]["likeCount"])/1;
            }

            if( (intval($r["items"][0]["statistics"]["likeCount"]) + intval($r["items"][0]["statistics"]["dislikeCount"])) < 25 ){
                if( defined("debug") ){
                    echo "Zu wenige Likes!<br>";
                }

                return false;
            }

            if( ($like_ratio) <= 7 ){
                if( defined("debug") ){
                    echo "Bad ratio: " . $like_ratio . "<br>";
                }

                return false;
            }

            return true;
        }

        if( defined("debug") ){
            echo "WTF: " . $like_ratio . "<br>";
        }

        return false;
    }

    /*
     * CALL Util::transformSong for $name variable pls
     *
     */
    public static function searchInYoutubeAPI($name, $nameFirstArtist, $country = NULL){
        if( $country ){
            $result = HttpRequest::sendGetRequest(self::$YTSEARCHURL . urlencode($name) . "&regionCode=" . $country);
        }
        else{
            $result = HttpRequest::sendGetRequest(self::$YTSEARCHURL . urlencode($name));
        }

        $r = json_decode($result, true);

        if($r["items"] && count($r["items"]) > 0){
            for($i=0;$i<count($r["items"]);$i++){
                $item = $r["items"][$i];

                if( $item["snippet"] && $item["snippet"]["title"] ){
                    $value = self::checkTitle($item["snippet"]["title"], $nameFirstArtist, $i, $item["snippet"]["description"], $item["snippet"]["channelId"], $name);

                    if( $value !== FALSE ){
                        self::$videos[] = array("id" => $item["id"]["videoId"],
                                                "title" => $item["snippet"]["title"],
                                                "value" => $value);
                    }
                }
             }
        }

        $t_videos = array();

        foreach (self::$videos as $key => $row)
        {
            $t_videos[$key] = $row['value'];
        }

        array_multisort($t_videos, SORT_DESC, self::$videos);

        if( defined("debug") ){
            print_r(self::$videos);
            echo "<br>";
        }

        foreach (self::$videos as $_value) {
            if( self::validateVideo($_value["id"]) ){
                return $_value["id"];
            }
        }

        return false;
    }
    
    public static function testYoutube($song_id){
        define("debug", true);
        
        if( !Util::is_session_started() ){
            session_start();
        }
        
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

    public static function searchForVideo($name, $nameFirstArtist, $song_id = NULL, $country = NULL, $searchSoundCloud = FALSE){
        $db = SoundDetectiveDB::getInstance();

        if( strlen($name) < 3 ){
            die("Error: string too small!");
        }

        self::$videos = [];
        $name = preg_replace("/(^|[^a-z])(and|the|feat)([^a-z]|$)/", " ", $name);
        $nameFirstArtist = preg_replace("/(^|[^a-z])(and|the|feat)([^a-z]|$)/", " ", $nameFirstArtist);

        if( defined("debug") ){
            echo "Search for: " . $name . "<br>";
        }

        if( $song_id && !defined("debug") ){
            if( $country ){
                $db->where("song_id = ? AND country_code = ?", [$song_id,$country]);
            }
            else{
                $db->where("song_id = ?", [$song_id]);
            }

            $row = $db->ObjectBuilder()->getOne(Config::table_songs_youtube);

            // video_id exists in database
            if( $row ){
                // we dont need to refetch
                if( intval($row->last_update) > time() - self::$refetch ){
                    echo $row->video_id;

                    return true;
                }
                // we have to refetch
                else{
                    $video_id = self::searchInYoutubeAPI($name, $nameFirstArtist, $country);

                    // video_id is valid
                    if( $video_id ){
                        $db->where("song_id = ?", [$song_id]);
                        $db->update(Config::table_songs_youtube, array("last_update" => time(),
                                                                        "video_id" => $video_id));

                        echo $video_id;
                        return true;
                    }

                    $db->where("song_id = ?", [$song_id]);
                    $db->update(Config::table_songs_youtube, array("last_update" => time(),
                        "video_id" => ""));
                }

                return false;
            }
            // we need to insert after we searched for it
            else{
                $video_id = self::searchInYoutubeAPI($name, $nameFirstArtist, $country);

                // video_id is valid
                if( $video_id ){
                    if( $country ){
                        $db->insert(Config::table_songs_youtube, array("last_update" => time(),
                                                                          "video_id" => $video_id,
                                                                          "country_code" => $country,
                                                                          "song_id" => $song_id));
                    }
                    else{
                        $db->insert(Config::table_songs_youtube, array("last_update" => time(),
                                                                          "video_id" => $video_id,
                                                                          "song_id" => $song_id));
                    }


                    echo $video_id;
                    return true;
                }

                if( $country ){
                        $db->insert(Config::table_songs_youtube, array("last_update" => time(),
                                                                          "video_id" => "",
                                                                          "country_code" => $country,
                                                                          "song_id" => $song_id));
                }

                return true;
            }
        }
        else{
            echo self::searchInYoutubeAPI($name, $nameFirstArtist, $country);
            return true;
        }
    }
}