<?php
namespace SoundDetective\Update;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

set_time_limit(600);

require_once '../SoundDetectiveAutoloader.php';

$classLoader = new \SoundDetective\SoundDetectiveAutoloader();

// register the autoloader
$classLoader->register();
$classLoader->addNamespace('SoundDetective', '../../app');

use SoundDetective\Util\Log;
use SoundDetective\YouTube;
use SoundDetective\Util\Util;

//$relationCalculator = new RelationCalculation(true);
//$relationCalculator->calculateRecommendedArtists(34);

//$spotifyHandler = new SpotifyUpdateCrawler();

//print_r($spotifyHandler->getSpotifyRequest("audio-features/5aNt0z5djvwsPflqoEXd7c"));

//echo count(['a' => 1, 'b' => 2, 'c' => 3]);
//$relationCalculator->insertCalculatedRelated();

//YouTube::testYoutube(360036);

//phpinfo();

//echo json_encode($_GET);
/*$crawler = new SpotifyUpdateCrawler();
$r = $crawler->getSpotifyRequest("recommendations?seed_genres=house&min_popularity=50&market=US");


foreach ($r["tracks"] as $key => $value){
    echo $value["artists"][0]["name"] . " - " . $value["name"] . " :: <a target=\"_blank\" href=\"" . $value["preview_url"] . "\">Klick</a><br>";
}

print_r($r["seeds"]);*/

?>
<html>
<head>
    <title>Update Control - Sound Detective</title>
    <script type="text/javascript">
        window.setTimeout(function(){
            //window.location.href = window.location.href;
        }, 500);
    </script>
</head>
<body>
<?php echo Log::getInstance()->getLog("<br>"); ?>
</body>
</html>