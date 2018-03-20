<?php
namespace SoundDetective;

use SoundDetective\Util\Config;
use Exception;
use SoundDetective\Util\HttpRequest;
use SoundDetective\Util\SoundDetectiveDB;

class UserSession{
	public static function setUserId($id){
		$db = SoundDetectiveDB::getInstance();
		$_SESSION['user_id'] = $id;

		$db->where("id = ?", [$id]);
		$db->update(Config::table_user, array("last_login" => time()));
	}

	public static function getUserId(){
		if( !isset($_SESSION['user_id']) ){
			return false;
			//throw new Exception("No user id is set!");
		}

		return $_SESSION['user_id'];
	}

	public static function is_connected_to_spotify(){
		if( !self::getUserId() ){
			return false;
		}

		if( isset($_SESSION['access_token']) ){
			return true;
		}

		return false;
	}

	private static function connect_to_spotify($spotify_id){
		if( !self::getUserId() ){
			return false;
		}

		$db = SoundDetectiveDB::getInstance();

		$db->where("spotify_id = ?", [$spotify_id]);
		$db->update(Config::table_user, ["spotify_id" => null]);

		$db->where("id = ?", [self::getUserId()]);
		$db->update(Config::table_user, ["spotify_id" => $spotify_id]);
	}

	public static function login($name, $password){
		unset($_SESSION['user_id']);
		unset($_SESSION['access_token']);
		unset($_SESSION['refresh_token']);
		unset($_SESSION['facebook_login']);

		$db = SoundDetectiveDB::getInstance();

		$db->where("name = ? AND password = ?", [$name, $password]);
		$user = $db->getOne(Config::table_user);

		if( !$user ){
			echo "Username does not exist or password is wrong!";
			return false;
		}
		else{
			$user_id = $user["id"];

			self::setUserId($user_id);

			header('Content-Type: text/plain');
			return true;
		}
	}

	public static function facebook_login($id, $name, $access_token){
		unset($_SESSION['user_id']);
		unset($_SESSION['access_token']);
		unset($_SESSION['refresh_token']);
		unset($_SESSION['facebook_login']);

		$res = HttpRequest::sendGetRequest("https://graph.facebook.com/debug_token?input_token=$access_token&access_token=" . Config::facebook_access);
		$r = json_decode($res, true);

		$db = SoundDetectiveDB::getInstance();

		if( $r["data"] && $r["data"]["user_id"] == $id ){
			$db->where("facebook_id = ?", [$id]);
			$user = $db->getOne(Config::table_user);

			if( $user ){
				self::setUserId($user["id"]);
				$_SESSION["facebook_login"] = true;
				return true;
			}
			else{
				$db->insert(Config::table_user, array("facebook_name" => $name,
					"facebook_id" => $id,
					"last_login" => time(),
					"created" => time()));

				self::setUserId($db->getInsertId());
				$_SESSION["facebook_login"] = true;
				return true;
			}
		}
	}

	public static function spotify_login($name, $spotify_id, $access_token = NULL){
		unset($_SESSION['user_id']);
		unset($_SESSION['facebook_login']);

		if( !$spotify_id || !$name ){
			return false;
		}

		// Check if its the real one
		if( $access_token && $access_token != $_SESSION["access_token"] ){
			return false;
		}

		$db = SoundDetectiveDB::getInstance();

		$db->where("spotify_id = ?", [$spotify_id]);
		$user = $db->getOne(Config::table_user);

		if( $user ){
			self::setUserId($user["id"]);
			return true;
		}
		else{
			return self::register(null, $name, null, $spotify_id);
		}
	}

	public static function logout(){
		session_unset();
	}

	public static function register($email, $name = null, $password = null, $spotify_id = null){
		try{
			$db = SoundDetectiveDB::getInstance();

			if( $password ){
				$db->insert(Config::table_user, array("name" => $name,
														 "email" => $email,
														 "password" => $password,
														 "last_login" => time(),
														 "created" => time()));
			}
			else if ( $spotify_id ){
				$db->insert(Config::table_user, array("spotify_name" => $name,
															"spotify_id" => $spotify_id,
															"last_login" => time(),
															"created" => time()));
			}

			self::setUserId($db->getInsertId());
			return true;
		}
		catch(Exception $e){
			if( defined('debug') ){
				echo "Error: " . $e->getMessage();
			}

			return false;
		}
	}
}
?>