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

$crawler = new SpotifyUpdateCrawler();

$crawler->updateArtistsLastFMImages();
//$crawler->updateCharts();

//$crawler->discoverFromPlaylists();
//$crawler->updateTracks();

//$crawler->updateArtistsTopSongs();
//$crawler->updateRelatedArtists(false);

//$crawler->resetArtistRelatedCrawling();
//$crawler->resetSongCrawling();
//$crawler->resetArtistCalculation();

//$relationCalculator = new RelationCalculation();
//$relationCalculator->calculateRelated(3);

//$relationCalculator->insertCalculatedRelated();

?>
<html>
    <head>
        <title>Update Control - Sound Detective</title>
        <script type="text/javascript">
            window.setTimeout(function(){
                  window.location.href = window.location.href;
            }, 500);
        </script>
    </head>
    <body>
        <?php echo Log::getInstance()->getLog("<br>"); ?>
    </body>
</html>