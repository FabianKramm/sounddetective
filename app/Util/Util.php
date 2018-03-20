<?php
namespace SoundDetective\Util;

class Util{
	/**
	 * @return bool
	 */
	public static function is_session_started()
	{
		if ( php_sapi_name() !== 'cli' ) {
			if ( version_compare(phpversion(), '5.4.0', '>=') ) {
				return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
			} else {
				return session_id() === '' ? FALSE : TRUE;
			}
		}
		return FALSE;
	}

	public static function parseResultSet($result){
		$resultSet = [];

		if( $result && count($result) > 0 ){
			foreach( $result as $row ){
				$song = [];

				$song['db_id'] = strval($row->db_id);
				$song['title'] = strval($row->title);
				$song['artist_name'] = strval($row->artist_name);

				if( isset($row->is_in_collection) && $row->is_in_collection ){
					$song['is_in_collection'] = true;
				}
				else if( isset($row->created) ){
					$song['is_in_collection'] = strval($row->created);
				}

				if( isset($row->artist_id) && $row->artist_id !== false ){
					$song['db_artist_id'] = strval($row->artist_id);
				}

				if( isset($row->artist_id2) && $row->artist_id2 !== false ){
					$song['db_artist_id2'] = strval($row->artist_id2);
				}

				if( isset($row->artist_id3) && $row->artist_id3 !== false ){
					$song['db_artist_id3'] = strval($row->artist_id3);
				}

				if( $row->spotify_id ){
					$song['available_spotify_id'] = $row->spotify_id;
				}

				$resultSet[] = $song;
			}
		}

		return $resultSet;
	}

	public static function getPercentageFromPopularity($pop){
		if( $pop >= 75 ){
			return 0;
		}
		else if( $pop >= 65 ){
			return 0.045;
		}
		else if ( $pop >= 55 ){
			return 0.11;
		}
		else if ( $pop >= 50 ){
			return 0.155;
		}
		else if ( $pop >= 45 ){
			return 0.21;
		}
		else if ( $pop >= 40 ){
			return 0.25;
		}
		else if ( $pop >= 30 ){
			return 0.38;
		}
		else if ( $pop >= 20 ){
			return 0.47;
		}
		else{
			return 1;
		}
	}

	/*
	 * Please use arrays!
	 */
	public static function weightedEuclideanDistance($vector1, $vector2, $weights){
		if( count($vector1) != count($vector2) ){
			return -1;
		}

		$t = 0;

		for ($i=0; $i < count($vector1); $i++) {
			$t += $weights[$i] * pow($vector1[$i] - $vector2[$i], 2);
		}

		return sqrt($t);
	}

	public static function getIPAddress(){
		// Known prefix
		$v4mapped_prefix_hex = '00000000000000000000ffff';
		$v4mapped_prefix_bin = pack("H*", $v4mapped_prefix_hex);

		// Or more readable when using PHP >= 5.4
		# $v4mapped_prefix_bin = hex2bin($v4mapped_prefix_hex);

		// Parse
		$addr = $_SERVER['REMOTE_ADDR'];
		$addr_bin = inet_pton($addr);
		if( $addr_bin === FALSE ) {
		  // Unparsable? How did they connect?!?
		  die('Invalid IP address');
		}

		// Check prefix
		if( substr($addr_bin, 0, strlen($v4mapped_prefix_bin)) == $v4mapped_prefix_bin) {
		  // Strip prefix
		  $addr_bin = substr($addr_bin, strlen($v4mapped_prefix_bin));
		}

		// Convert back to printable address in canonical form
		return inet_ntop($addr_bin);
	}

	public static function getLocation(){
		$db = SoundDetectiveDB::getInstance();

		$address = explode(".", self::getIPAddress());
		$IpNumber = 16777216*intval($address[0]) + 65536*intval($address[1]) + 256*intval($address[2]) + intval($address[3]);

		$db->where("$IpNumber BETWEEN ip_from AND ip_to");

		$row = $db->ObjectBuilder()->getOne(Config::table_ip2location, ["country_code"]);

		if( $row ){
			return $row->country_code;
		}
		else{
			return null;
		}
	}

	public static function getRandomString($length = 8) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$string = '';

		for ($i = 0; $i < $length; $i++) {
			$string .= $characters[mt_rand(0, strlen($characters) - 1)];
		}

		return $string;
	}

	public static function array_sort($array, $on, $order=SORT_ASC)
	{
		$new_array = array();
		$sortable_array = array();

		if (count($array) > 0) {
			foreach ($array as $k => $v) {
				if (is_array($v)) {
					foreach ($v as $k2 => $v2) {
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}

			switch ($order) {
				case SORT_ASC:
					asort($sortable_array);
				break;
				case SORT_DESC:
					arsort($sortable_array);
				break;
			}

			foreach ($sortable_array as $k => $v) {
				$new_array[$k] = $array[$k];
			}
		}

		return $new_array;
	}

	public static function linear_interpolation($X, $X1, $Y1, $X2, $Y2){
		return ( ( $X - $X1 ) * ( $Y2 - $Y1) / ( $X2 - $X1) ) + $Y1;
	}

	// Convert an UTF-8 encoded string to a single-byte string suitable for
	// functions such as levenshtein.
	//
	// The function simply uses (and updates) a tailored dynamic encoding
	// (in/out map parameter) where non-ascii characters are remapped to
	// the range [128-255] in order of appearance.
	//
	// Thus it supports up to 128 different multibyte code points max over
	// the whole set of strings sharing this encoding.
	//
	public static function utf8_to_extended_ascii($str, &$map)
	{
			// find all multibyte characters (cf. utf-8 encoding specs)
			$matches = array();
			if (!preg_match_all('/[\xC0-\xF7][\x80-\xBF]+/', $str, $matches))
				return $str; // plain ascii string

			// update the encoding map with the characters not already met
			foreach ($matches[0] as $mbc)
				if (!isset($map[$mbc]))
					$map[$mbc] = chr(128 + count($map));

			// finally remap non-ascii characters
			return strtr($str, $map);
		}

		public static function clean($string) {
	   $string = str_replace('-', ' ', $string); // Replaces all spaces with hyphens.

	   return preg_replace('/[^A-Za-z0-9\s\%\+]/', '', $string); // Removes special chars.
	}

	public static function seems_utf8($str)
	{
		$length = strlen($str);
		for ($i=0; $i < $length; $i++) {
			$c = ord($str[$i]);
			if ($c < 0x80) $n = 0; # 0bbbbbbb
			elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
			elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
			elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
			elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
			elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
			else return false; # Does not match any model
			for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
				if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
					return false;
			}
		}
		return true;
	}

	/**
	 * Converts all accent characters to ASCII characters.
	 *
	 * If there are no accent characters, then the string given is just returned.
	 *
	 * @param string $string Text that might have accent characters
	 * @return string Filtered string with replaced "nice" characters.
	 */
	public static function remove_accents($string) {
		if ( !preg_match('/[\x80-\xff]/', $string) )
			return $string;

		if (Util::seems_utf8($string)) {
			$chars = array(
			// Decompositions for Latin-1 Supplement
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			// Euro Sign
			chr(226).chr(130).chr(172) => 'E',
			// GBP (Pound) Sign
			chr(194).chr(163) => '');

			$string = strtr($string, $chars);
		} else {
			// Assume ISO-8859-1 if not UTF-8
			$chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
				.chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
				.chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
				.chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
				.chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
				.chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
				.chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
				.chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
				.chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
				.chr(252).chr(253).chr(255);

			$chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

			$string = strtr($string, $chars['in'], $chars['out']);
			$double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
			$double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
			$string = str_replace($double_chars['in'], $double_chars['out'], $string);
		}

		return $string;
	}

	public static function eraseBracketsAndSpecialChars($name){
		// erase unnecessary information from $name
		$name = preg_replace('/\\([^\\)]*\\)/', ' ', $name);
		$name = preg_replace('/\\[[^\\]]*\\]/', ' ', $name);
		$name = preg_replace('/\\{[^\\}]*\\}/', ' ', $name);
		$name = preg_replace('/\\<[^\\>]*\\>/', ' ', $name);
		$name = preg_replace('/(\\(|\\[|\\{).*/', ' ', $name);

		$name = preg_replace('/[^A-Za-z0-9\\s]/', ' ', $name);
		$name = preg_replace('/\\s+/', ' ', $name);

		return trim($name);
	}

	// Didactic example showing the usage of the previous conversion function but,
	// for better performance, in a real application with a single input string
	// matched against many strings from a database, you will probably want to
	// pre-encode the input only once.
	//
	public static function levenshtein_utf8($s1, $s2)
	{
		$s1 = strtolower($s1);
		$s2 = strtolower($s2);

		$charMap = array('ä' => 'ae',
						 'ö' => 'oe',
						 'ü' => 'ue',
						 'ô' => 'o',
						 'é' => 'e',
						 'è' => 'e',
						 'á' => 'a',
						 'à' => 'a',
						 'ó' => 'o',
						 'ò' => 'o',
						 'í' => 'i',
						 'ì' => 'i',
						 'î' => 'i',
						 'û' => 'u',
						 'ú' => 'u',
						 'ù' => 'u',
						 'ß' => 'ss',
						 'ë' => 'e',
						 'ê' => 'e');

		$s1 = self::utf8_to_extended_ascii($s1, $charMap);
		$s2 = self::utf8_to_extended_ascii($s2, $charMap);

		return levenshtein($s1, $s2);
	}

	public static function isSameSong($song1, $song2){
		if( self::levenshtein_utf8($song1, $song2) <= 5 ){
			return true;
		}
		else{
			return false;
		}
	}

	public static function formatTitleSimple($title){
		$replaceArr = array("\\([^\\)]*(single|mono|album|club|vip|radio|original|short|extended|edit|remastered|vocal|festival|explicit album|video)\s?(version|edit|mix)?\\)",
							"\\[[^\\]]*(single|mono|album|club|vip|radio|original|short|extended|edit|remastered|vocal|festival|explicit album|video)\s?(version|edit|mix)?\\]",
							"\\-.*(single|mono|album|radio|club|vip|original|short|extended|edit|remastered|vocal|festival|explicit album|video)\s?(version|edit|mix)?",
							"(\\(|\\[)mix cut(\\)|\\])");

		foreach ($replaceArr as $value) {
			$title = preg_replace("/(^|[^A-Za-z])(" . $value . ")([^A-Za-z]|$)/i", ' ',  $title);
		}

		return trim($title);
	}

	public static function formatTitle($title, $use_brackets = FALSE){
		$title = strtolower(Util::remove_accents($title));

		$remix = "";

		 // remix
		 if( preg_match("/(\\(|\\{|\\[|\\-)(.*?remix)/", $title, $matches) ){
			 $remix = $matches[2];
		 }

		 // title cut -
		 if( ($idx = strpos($title, " - ")) !== FALSE ){
			 $title = substr($title, 0, $idx);
		 }

		 // erase unnecessary information from $name
		 $title = preg_replace('/\\([^\\)]*\\)/', ' ', $title);
		 $title = preg_replace('/\\[[^\\]]*\\]/', ' ', $title);
		 $title = preg_replace('/\\{[^\\}]*\\}/', ' ', $title);
		 $title = preg_replace('/\\<[^\\>]*\\>/', ' ', $title);
		 $title = preg_replace('/(\\(|\\[|\\{).*/', ' ', $title);
		 $title = trim($title);

		 if( strlen($remix) > 0 ){
			 if( $use_brackets ){
				 // only used when showing songs to frontend
				 return $title . " (" . ucwords(trim($remix)) . ")";
			 }
			 else{
				 return $title . " " . $remix;
			 }
		 }
		 else{
			 return $title;
		 }
	}

	public static function formatArtist($artist){
		$artist = explode(";;", $artist)[0];

		$artist = strtolower(Util::remove_accents($artist));
		$artist = preg_replace('/\\([^\\)]*\\)/', ' ', $artist);

		$artist = preg_replace('/[^A-Za-z0-9\\s]/', ' ', $artist);
		$artist = preg_replace('/\\s+/', ' ', $artist);

		return $artist;
	}

	public static function transformSong($artist, $title){
		 $title = self::formatTitle($title);
		 $artist = self::formatArtist($artist);

		 $returnString = $artist . " " . $title;

		 $returnString = preg_replace('/[^A-Za-z0-9\\s]/', ' ', $returnString);
		 $returnString = preg_replace('/\\s+/', ' ', $returnString);

		 return trim($returnString);
	}
}