<?php
namespace SoundDetective\Update;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

require_once '../SoundDetectiveAutoloader.php';

$classLoader = new \SoundDetective\SoundDetectiveAutoloader();

// register the autoloader
$classLoader->register();
$classLoader->addNamespace('SoundDetective', '../../app');

$artist = SoundDetectiveUpdateDB::getInstance()->getOne(Config::table_update_artists);
$song = SoundDetectiveUpdateDB::getInstance()->getOne(Config::table_update_artists);
?>
<html>
    <head>
        <title>Overview</title>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js"></script>
        <script type="text/javascript">
            var updateRelated = new Date(<?php echo (intval($artist["last_update_related"])*1000) ?>).toLocaleString();
            var updateRelatedCalculated = new Date(<?php echo (intval($artist["last_update_related_calculated"])*1000) ?>).toLocaleString();
            var updateTopSongs = new Date(<?php echo (intval($artist["last_update_top"])*1000) ?>).toLocaleString();
            var updateSongs = new Date(<?php echo ($song["last_update"]*1000) ?>).toLocaleString();

            $(document).ready(function(){
                $(".updateRelated").html(updateRelated);
                $(".updateRelatedCalculated").html(updateRelatedCalculated);
                $(".updateTopSongs").html(updateTopSongs);
                $(".updateSongs").html(updateSongs);
            });
        </script>
    </head>
    <body>
        <div>
            <span>Update Related:</span><span class="updateRelated"></span>
        </div>
        <div>
            <span>Update Related Calculated:</span><span class="updateRelatedCalculated"></span>
        </div>
        <div>
            <span>Update Top Songs:</span><span class="updateTopSongs"></span>
        </div>
        <div>
            <span>Update Songs:</span><span class="updateSongs"></span>
        </div>
    </body>
</html>

