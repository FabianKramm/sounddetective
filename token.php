<?php
namespace SoundDetective;

use SoundDetective\Util\HttpRequest;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

ini_set('session.cookie_domain', '.sounddetective.net' );

//include_once $ROOT . "php/classes/Router.class.php";
require_once './app/SoundDetectiveAutoloader.php';

$classLoader = new SoundDetectiveAutoloader();

// register the autoloader
$classLoader->register();
$classLoader->addNamespace('SoundDetective', './app');

session_start();

unset($_SESSION['user_id']);
unset($_SESSION['access_token']);
unset($_SESSION['refresh_token']);
unset($_SESSION['facebook_login']);

use SoundDetective\Util\Config;

if( isset($_GET["code"]) ){
    $redirect_uri = Config::playUrl . "/token.php";

    if( $_SERVER[HTTP_HOST] == "www.sounddetective.net" ){
        $redirect_uri = Config::playUrl . "/token.php";
    }

    $res = HttpRequest::sendPostRequest("https://accounts.spotify.com/api/token", array("grant_type" => "authorization_code",
                                                                                        "code" => $_GET["code"],
                                                                                        "redirect_uri" => $redirect_uri), array("Authorization: Basic " . Config::spotify_redirect_token));

    $r = json_decode($res, true);

    if( $r["access_token"] && $r["refresh_token"] ){
        $_SESSION["access_token"] = $r["access_token"];
        $_SESSION["refresh_token"] = $r["refresh_token"];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
</head>
<body>
    <script type="text/javascript">
        try{
            // send token to parent window
            if( window.opener && window.opener.spotifyTokenCallback ){
                window.opener.spotifyTokenCallback("<?php if( isset($_SESSION["access_token"]) && $_SESSION["access_token"]) { echo $_SESSION["access_token"]; } ?>");
                window.close();
            }
	    else{
                var token = "<?php if( isset($_SESSION["access_token"]) && $_SESSION["access_token"]) { echo $_SESSION["access_token"]; } ?>";
            
           	window.opener.postMessage({type:"spotifyLogin", data: token}, "*");
            	window.close(); 
            }
        }
        catch(e){
            console.info(e.message);
            var token = "<?php if( isset($_SESSION["access_token"]) && $_SESSION["access_token"]) { echo $_SESSION["access_token"]; } ?>";
            
            window.opener.postMessage({type:"spotifyLogin", data: token}, "*");
            window.close(); 
        }
    </script>
</body>
</html>