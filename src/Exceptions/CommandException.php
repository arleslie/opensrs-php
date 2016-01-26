<?php

namespace arleslie\OpenSRS;

class CommandException extends \Exception
{
	private $recommendation;

	public function __construct($error, $recommendation, $errorcode = 0, \Exception $previous = null)
	{
		$this->recommendation = $recommendation;

		parent::__construct($error, $errorcode, $previous);
	}

	public function getRecommendation()
	{
		return $this->recommendation;
	}
}