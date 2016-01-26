<?php

namespace arleslie\OpenSRS;

class AuthenicationException extends \Exception
{
	private $recommendation;

	public function __construct($error, $recommendation, \Exception $previous = null)
	{
		$this->recommendation = $recommendation;

		parent::__construct($error, 0, $previous);
	}

	public function getRecommendation()
	{
		return $this->recommendation;
	}
}