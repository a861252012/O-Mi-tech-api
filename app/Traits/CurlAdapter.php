<?php
/**
 * HTTP發送共用層
 * @author Weine
 * @Date 2019/11/14
 */

namespace App\Traits;

use GuzzleHttp\Client;

trait CurlAdapter
{
	/**
	 * 發送POST請求
	 * @param $url
	 * @param array $dataArray
	 * @param string $dataType
	 * @return bool|mixed|\Psr\Http\Message\ResponseInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function post($url, $dataArray = [], $header = [], $dataType = 'json')
	{
		$client = new Client();

		return $client->request('POST', $url, [
			'headers' => $header,
			$dataType => $dataArray,
		]);
	}

	/**
	 * 發送GET請求
	 * @param $url
	 * @param null $dataArray
	 * @return mixed|\Psr\Http\Message\ResponseInterface
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function get($url, $header = [])
	{
		$client = new Client();

		return $client->request('GET', $url, $header);
	}
}