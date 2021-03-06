<?php

namespace arleslie\OpenSRS;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Psr7\Response;
use SimpleXMLElement;

trait Helper
{
	protected $guzzle;
	private $apikey;

	public function __construct(Guzzle $guzzle, string $apikey)
	{
		$this->guzzle = $guzzle;
		$this->apikey = $apikey;
	}

	protected function send(string $action, array $attributes)
	{
		$xml = $this->castToXML(strtoupper($action), $attributes)->asXML();

		try {
			$response = $this->guzzle->request('POST', '/', [
				'headers' => [
					'X-Signature' => md5(md5($xml . $this->apikey) . $this->apikey)
				],
				'body' => $xml
			]);
		} catch (\Exception $e) {
			throw new ConnectionException(
				"Unable to connect to OpenSRS API.",
				"Please verify your IP is whitelisted in your OpenSRS Account Settings.",
				0,
				$e
			);
		}

		return $this->parse($response);
	}

	protected function parse(Response $response)
	{
		$xml = new SimpleXMLElement((string) $response->getBody());
		$hasError = (string) $xml->xpath('//OPS_envelope/body/data_block/dt_assoc/item[@key="is_success"]')[0] === '0';

		if ($hasError) {
			$object = $xml->xpath('//OPS_envelope/body/data_block/dt_assoc/item[@key="object"]');
			$object = !empty($object) ? (string) $object[0] : 'AUTHENTICATE';

			$errorcode = intval((string) $xml->xpath('//OPS_envelope/body/data_block/dt_assoc/item[@key="response_code"]')[0]);
			$errormessage = (string) $xml->xpath('//OPS_envelope/body/data_block/dt_assoc/item[@key="response_text"]')[0];

			switch ($object) {
				case 'AUTHENTICATE':
					switch ($errorcode) {
						case 400:
							throw new AuthenicationException('Invalid Username', 'Check that the username has been entered correctly.', $errorcode);
							break;
						case 401:
							throw new AuthenicationException('Invalid API Key', 'Check that the API Key has been entered correctly.', $errorcode);
							break;
						default:
							throw new \Exception($errormessage, $errorcode);
							break;
					}
					break;
				default:
					switch ($errorcode) {
						case 400:
							throw new CommandException($errormessage, 'Please report this as a bug.', $errorcode);
							break;
						default:
							throw new \Exception($errormessage, $errorcode);
							break;
					}
					break;
			}
		}

		$return = [];
		$attributes = $xml->xpath('//OPS_envelope/body/data_block/dt_assoc/item[@key="attributes"]/dt_assoc/*');
		foreach ($attributes as $attribute) {
			$return[(string) $attribute->attributes()['key']] = (string) $attribute;
		}

		return $return;
	}

	private function castToXML(string $action, array $values)
	{
		$xml = new SimpleXMLElement("<OPS_envelope></OPS_envelope>");
		$xml->addChild('header')->addChild('version', '0.9');

		$body = $xml->addChild('body')->addChild('data_block')->addChild('dt_assoc');
		$body->addChild('item', 'XCP')->addAttribute('key', 'protocol');
		$body->addChild('item', 'DOMAIN')->addAttribute('key', 'object');
		$body->addChild('item', $action)->addAttribute('key', 'action');

		$attributes = $body->addChild('item');
		$attributes->addAttribute('key', 'attributes');

		$this->buildAttributes($attributes->addChild('dt_assoc'), $values);

		return $xml;
	}

	private function &buildAttributes(SimpleXMLElement $xml, array $values)
	{
		foreach ($values as $key => $value) {
			if (is_array($value)) {
				$child = $xml->addChild('item')->addAttribute('key', $key);

				if ($this->__isArrayAssoc($value)) {
					$this->buildAttributes($child);
				} else {
					$this->buildAttributes($child);
				}
			} else {
				$xml->addChild('item', $value)->addAttribute('key', $key);
			}
		}

		return $xml;
	}

	private function __isArrayAssoc(array $array)
	{
		return array_keys($array) !== range(0, count($array) - 1);
	}
}