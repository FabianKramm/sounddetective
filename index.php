<?php
namespace SoundDetective;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

ini_set('session.cookie_domain', '.sounddetective.net' );

if( $_SERVER["HTTP_HOST"] == "sounddetective.net" || $_SERVER["HTTP_HOST"] == "www.sounddetective.net" ){
    include_once "./tmpl/onepager.php";
    exit();
}

//include_once $ROOT . "php/classes/Router.class.php";
require_once './app/SoundDetectiveAutoloader.php';

$classLoader = new SoundDetectiveAutoloader();

// register the autoloader
$classLoader->register();
$classLoader->addNamespace('SoundDetective', './app');

use SoundDetective\Util\Config;
use SoundDetective\Util\Util;
use SoundDetective\Util\SoundDetectiveDB;

session_start();

/**
 * Log
 */
$user_agent     =   $_SERVER['HTTP_USER_AGENT'];

function getOS() {
    global $user_agent;

    $os_platform    =   "Unknown OS Platform";
    $os_array       =   array(
        '/windows nt 10/i'     =>  'Windows 10',
        '/windows nt 6.3/i'     =>  'Windows 8.1',
        '/windows nt 6.2/i'     =>  'Windows 8',
        '/windows nt 6.1/i'     =>  'Windows 7',
        '/windows nt 6.0/i'     =>  'Windows Vista',
        '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     =>  'Windows XP',
        '/windows xp/i'         =>  'Windows XP',
        '/windows nt 5.0/i'     =>  'Windows 2000',
        '/windows me/i'         =>  'Windows ME',
        '/win98/i'              =>  'Windows 98',
        '/win95/i'              =>  'Windows 95',
        '/win16/i'              =>  'Windows 3.11',
        '/macintosh|mac os x/i' =>  'Mac OS X',
        '/mac_powerpc/i'        =>  'Mac OS 9',
        '/linux/i'              =>  'Linux',
        '/ubuntu/i'             =>  'Ubuntu',
        '/iphone/i'             =>  'iPhone',
        '/ipod/i'               =>  'iPod',
        '/ipad/i'               =>  'iPad',
        '/android/i'            =>  'Android',
        '/blackberry/i'         =>  'BlackBerry',
        '/webos/i'              =>  'Mobile'
    );

    foreach ($os_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $os_platform    =   $value;
        }
    }

    return $os_platform;
}

function getBrowser() {
    global $user_agent;

    $browser        =   "Unknown Browser";
    $browser_array  =   array(
        '/msie/i'       =>  'Internet Explorer',
        '/firefox/i'    =>  'Firefox',
        '/safari/i'     =>  'Safari',
        '/chrome/i'     =>  'Chrome',
        '/edge/i'       =>  'Edge',
        '/opera/i'      =>  'Opera',
        '/netscape/i'   =>  'Netscape',
        '/maxthon/i'    =>  'Maxthon',
        '/konqueror/i'  =>  'Konqueror',
        '/mobile/i'     =>  'Handheld Browser'
    );

    foreach ($browser_array as $regex => $value) {
        if (preg_match($regex, $user_agent)) {
            $browser    =   $value;
        }
    }

    return $browser;
}

$user_id = (isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : -1;
$action = null;
$params = null;

if ( isset($_GET["a"]) ){
    $action = $_GET["a"];
    $params = json_encode($_GET);
}

if ( isset($_POST["a"]) ){
    $action = $_POST["a"];
    $params = json_encode($_POST);
}

if ( !isset($_SESSION['country_code']) )
{
    $_SESSION['country_code'] = Util::getLocation();
}

try{
    SoundDetectiveDB::getInstance()->insert(Config::table_user_track, [
        "country" => $_SESSION['country_code'],
        "ip" => $_SERVER["REMOTE_ADDR"],
        "browser" => getBrowser(),
        "platform" => getOS(),
        "user_id" => $user_id,
        "action" => $action,
        "params" => $params,
        "timestamp" => time()
    ]);
}
catch(\Exception $e){
    print_r($e);
}

Router::route();

// Render
include_once "./tmpl/main.tmpl.php";
?>