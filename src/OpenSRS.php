<?php

namespace arleslie\OpenSRS;

use GuzzleHttp\Client as Guzzle;

class OpenSRS
{
	const API_URL = "https://rr-n1-tor.opensrs.net:55443/";
	const API_TEST_URL = "https://horizon.opensrs.net:55443/";

	private $guzzle;
	private $apikey;
	private $apis = [];

	public function __construct($username, $apikey, $testmode = false)
	{
		$this->apikey = $apikey;

		$this->guzzle = new Guzzle([
			'base_uri' => $testmode ? self::API_TEST_URL : self::API_URL,
			'defaults' => [
				'verify' => false
			],
			'headers' => [
				'content-type' => 'text/xml',
				'X-Username' => $username
			]
		]);
	}

	private function _getAPI($api)
	{
		if (!isset($this->apis[$api])) {
			$class = 'arleslie\\OpenSRS\\APIs\\' . $api;
			$this->apis[$api] = new $class($this->guzzle, $this->apikey);
		}

		return $this->apis[$api];
	}

	public function domain() {
		return $this->_getAPI('Domain');
	}
}