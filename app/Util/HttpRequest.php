<?php
namespace SoundDetective\Util;

class HttpRequest{
    /*
    * Sends an http get request to the specified url
    */
    public static function sendGetRequest($url, $HTTP_HEADERS = array(), Log $log = NULL){
        if( $log ){
            $log->write("GET: " . $url);
        }

        $ch = curl_init();
        $timeout = 5;

        $options = array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_CONNECTTIMEOUT => $timeout,     // follow redirects
            CURLOPT_HTTPHEADER     => array_merge(array("Accept-Language: en-US;q=0.6,en;q=0.4","User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:33.0) Gecko/20100101 Firefox/33.0"), $HTTP_HEADERS)
        );

        curl_setopt_array($ch, $options);
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }

    /*
    * Sends an http post request to the specified url
    *
    * @param $data: needs to be an associative array
    */
    public static function sendPostRequest($url, $data, $HTTP_HEADERS = array(), Log $log = NULL){
        if( $log ){
            $log->write("POST: " . $url);
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        if( count($HTTP_HEADERS) > 0 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $HTTP_HEADERS);
        }

        // in real life you should use something like:
        if( $data ){
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        // receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec ($ch);

        curl_close ($ch);

        return $server_output;
    }
}
