<?php

namespace qlixes\Bank;

use qlixes\Bank\BanksException;
use GuzzleHttp\Client;
use stdClass;

class Banks
{
    protected $bank;
    protected $client;

    protected $body = [];
    protected $headers = [];
    protected $options = [];

    function __construct($bankUri)
    {
        $this->bank = new stdClass();

        $this->bank->timezone = 'Asia/Jakarta';

        $this->headers['Accept'] = 'application/json';
        $this->headers['Content-Type'] = 'application/json';
    }

    function getBaseUri($baseUrl, $port = null)
    {
        $baseUrl .= ($port) ?? 443; //force to https

        $this->client = new Client([
            'base_uri' => $baseUrl
        ]);
    }
}