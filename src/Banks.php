<?php

namespace qlixes\Bank;

use GuzzleHttp\Client;

class Banks
{
	protected $client;

	function __construct()
	{
		$this->client = new Client();
	}
}