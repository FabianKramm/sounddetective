<?php
namespace SoundDetective;

use SoundDetective\Controller;
	
class Router{
	private static function getRoutes(){
		/*
		 * Format:
		 * 		request_type,
		 * 		conditions_that_must_be_fullfilled
		 * 		parameters, false = optional, true = required
		 * 		function to call
		 * 		exit after call
		 */
		return array(
			// register
			array(
			   $_POST,
			   array("a" => "registerv2"),
			   array("username" => false,
					 "password" => false,
					 "email" => false),
			   "Controller::registerV2",
			   true
			),
			// navigate
			array(
				$_GET,
				array("a" => "navigate"),
				array("page" => true),
				"Controller::navigate",
				false
			),
			// refresh token
			array(
				$_GET,
				array("a" => "refresh_token"),
				array(),
				"Controller::refreshToken",
				true
			),
			// love song
			array(
				$_GET,
				array("a" => "love"),
				array("id" => true),
				"Controller::loveSong",
				true
			),
			// remove song from collection
			array(
				$_GET,
				array("a" => "delete"),
				array("id" => true),
				"Controller::removeFromCollection",
				true
			),
			// charts
			array(
				$_GET,
				array("a" => "charts"),
				array("genre" => false),
				"Controller::getCharts",
				true
			),
			// autocomplete
			array(
				$_GET,
				array("a" => "autocomplete"),
				array("name" => true),
				"Controller::artistAutoComplete",
				true
			),
			// artist
			array(
				$_GET,
				array("a" => "artist"),
				array("name" => true),
				"Controller::getArtistId",
				true
			),
			// normal login
			array(
				$_POST,
				array("a" => "login",
					  "t" => "normal"),
				array("name" => true,
					  "password" => true),
				"UserSession::login",
				true
			),
			// facebook login
			array(
				$_GET,
				array("a" => "login",
					  "t" => "facebook"),
				array("id" => true,
					  "name" => true,
					  "access_token" => true),
				"UserSession::facebook_login",
				true
			),
			// spotify login
			array(
				$_GET,
				array("a" => "login",
					  "t" => "spotify"),
				array("name" => true,
					  "id" => true,
					  "access_token" => false),
				"UserSession::spotify_login",
				true
			),

			// logout
			array(
				$_GET,
				array("a" => "logout"),
				array(),
				"UserSession::logout",
				true
			),
			// get youtube id
			array(
				$_GET,
				array("a" => "youtube"),
				array("song_id" => true),
				"Controller::getYoutubeVideoId",
				true
			),
			// get youtube id
			array(
				$_GET,
				array("a" => "search_collection"),
				array("type" => true,
					  "search" => true),
				"Controller::searchCollection",
				true
			),
			// password forgotten
			array(
				$_POST,
				array("a" => "forgot_password"),
				array("email" => true),
				"Controller::resetPassword",
				true
			),
			// reset password
			array(
				$_GET,
				array("a" => "resetpassword"),
				array("id" => true,
					  "key" => true),
				"Controller::resetPasswordEmail",
				false
			),
			// change password
			array(
				$_POST,
				array("a" => "changepassword"),
				array("id" => true,
					  "key" => true,
					  "password" => true),
				"Controller::changePassword",
				true
			),
			// get detectives
			array(
				$_GET,
				array("a" => "getdetectives"),
				array(),
				"Controller::getDetectives",
				true
			),
			// apply detective
			array(
				$_POST,
				array("a" => "applydetective"),
				array("uuid" => true,
					  "es" => false),
				"Controller::applyDetective",
				true
			),
			// get detective details
			array(
				$_GET,
				array("a" => "getdetectivedetails"),
				array("uuid" => true),
				"Controller::getDetectiveDetails",
				true
			),
			// create/modify detective
			array(
				$_POST,
				array("a" => "createdetective"),
				array("name" => true,
					  "uuid" => false,
					  "artists" => false,
					  "mnsp" => false,
					  "mxsp" => false,
					  "mnap" => false,
					  "mxap" => false,
					  "mnrd" => false,
					  "mxrd" => false,
					  "exr" => false,
					  "exa" => false,
					  "exc" => false,
					  "exs" => false,
					  "exar" => false,
					  "image" => false),
				"Controller::createDetective",
				true
			),
			// apply temp detective
			array(
				$_POST,
				array("a" => "tempdetective"),
				array("artists" => false,
					  "mnsp" => false,
					  "mxsp" => false,
					  "mnap" => false,
					  "mxap" => false,
					  "mnrd" => false,
					  "mxrd" => false,
					  "exr" => false,
					  "exa" => false,
					  "exs" => false),
				"Controller::applyTempDetective",
				true
			),
			// echo song details
			array(
				$_POST,
				array("a" => "getsongdetails"),
				array("ids" => true),
				"Controller::echoSongDetails",
				true
			),
			// delete detective
			array(
				$_GET,
				array("a" => "deletedetective"),
				array("uuid" => true),
				"Controller::deleteDetective",
				true
			),
			// get detective excludes
			array(
				$_GET,
				array("a" => "getdetectiveexcludes"),
				array("uuid" => true,
					  "artists" => false,
					  "songs" => false),
				"Controller::getDetectiveExcludes",
				true
			),
			// get complete collection
			array(
				$_GET,
				array("a" => "getcompletecollection"),
				array(),
				"Controller::getCompleteCollection",
				true
			),
			// get artist details
			array(
				$_GET,
				array("a" => "getartistdetails"),
				array("artist_id" => true),
				"Controller::getArtistDetails",
				true
			),
			// get video id (also soundcloud)
			array(
				$_GET,
				array("a" => "getvideoid"),
				array("id" => true),
				"Controller::getVideoId",
				true
			),
			// exclude song from detective
			array(
				$_GET,
				array("a" => "excludefromdetective"),
				array("uuid" => true,
					 "artist_id" => false,
					 "song_id" => false),
				"Controller::excludeFromDetective",
				true
			),
			// get detective amount
			array(
				$_GET,
				array("a" => "getdetectiveamount"),
				array("artists" => true),
				"Controller::getArtistPoolCount",
				true
			),
			// get recaptcha html
			array(
				$_GET,
				array("a" => "getrecaptcha"),
				array(),
				"Controller::getRecaptchaHtml",
				true
			),
			// get popular artists
			array(
				$_GET,
				array("a" => "getpopularartists"),
				array(),
				"Controller::getPopularArtists",
				true
			),
			// report video
			array(
				$_GET,
				array("a" => "reportvideo"),
				array("id" => true),
				"Controller::reportVideo",
				true
			),
			// report video
			array(
				$_POST,
				array("a" => "massexclude"),
				array("uuid" => true,
					  "songs" => true),
				"Controller::massExcludeFromDetective",
				true
			)
		);
	}

	private static function routeConditions($type, $conditions){
		foreach ($conditions as $key => $value) {
			if( !isset($type[$key]) || $type[$key] !== $value ){
				return false;
			}
		}

		return true;
	}

	private static function getParameters($type, $parameters){
		$params = array();

		foreach ($parameters as $key => $value) {
			if( isset($type[$key]) ){
				array_push($params, $type[$key]);
			}
			else if( $value ){
				return false;
			}
			else if( !$value ){
				array_push($params, null);
			}
		}

		return $params;
	}

	/*
	 *
	 */
	public static function route(){
		$routes = self::getRoutes();

		// kind of a rate limit we don't want users to spam requests!!!
		foreach ( $routes as $route ){
			if( self::routeConditions($route[0], $route[1]) ){
				$params = self::getParameters($route[0], $route[2]);

				if( $params !== false ){
					if( count($route) === 4 || !$route[4] ){
						return call_user_func_array(__NAMESPACE__.'\\'.$route[3], $params);
					}
					else if( count($route) === 5 && $route[4] ){
						call_user_func_array(__NAMESPACE__.'\\'.$route[3], $params);
						exit();
					}
				}
			}
		}

		return false;
	}
}