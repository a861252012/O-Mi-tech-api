<?php
namespace App\Services\Email;

use App\Services\Email\Response;


class HttpClient {
	public function __construct($host) {
		$this->host = $host;
	}
	
	public function post($method, $url,$header,$param){
		/*
		$header = !empty( $header ) ? $header :'Content-type: application/x-www-form-urlencoded ';
		$options = array (
				'http' => array (
						'method' => 'POST',
						'header' => $header,
						'content' => http_build_query($param)
				)
		);
		$url = $this->host . "" . $url;
		$context = stream_context_create ( $options );
		$result = file_get_contents ( $url, false, $context );*/
		$header = !empty( $header ) ? $header :'Content-type: application/x-www-form-urlencoded ';
		$url = $this->host . "" . $url;
		$ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $result = curl_exec($ch);
        $errstr = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);

		return new Response ( $result );
	}
	
	public function mutilpost($method, $url, $body,$header) {
		/*$header = !empty ( $header ) ? $header :'Content-type: application/x-www-form-urlencoded ';
		$options = array (
				'http' => array (
						'method' => 'POST',
						'header' => $header,
						'content' => $body
				) 
		);
		$url = $this->host . "" . $url;
		$context = stream_context_create ( $options );
		$result = file_get_contents ( $url, false, $context );*/
		$header = !empty( $header ) ? $header :'Content-type: application/x-www-form-urlencoded ';
		$url = $this->host . "" . $url;
		$ch = curl_init(); //初始化CURL句柄
        curl_setopt($ch, CURLOPT_URL, $url); //设置请求的URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);
        $result = curl_exec($ch);
        $errstr = curl_error($ch);
        $curl_errno = curl_errno($ch);
        curl_close($ch);
        
		return new Response ( $result );
	}
}