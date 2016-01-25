<?php

namespace arleslie\OpenSRS\APIs;

class Domain {
	use \arleslie\OpenSRS\Helper;

	public function lookup($domain, $cache = true)
	{
		return $this->send('lookup', [
			'domain' => $domain,
			'no_cache' => !$cache
		]);
	}
}