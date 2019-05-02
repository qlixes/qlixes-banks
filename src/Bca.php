<?php

namespace qlixes\Bank;

use qlixes\Bank\Banks;

/**
 * BCA Library.
 *
 * @author     qlixes
 * @license    MIT
 * @copyright  (c) 2019, qlixes
 */
class Bca extends Banks
{
    function __construct($corp_id, $client_id, $client_secret, $api_key, $secret_key)
    {
        parent::__construct();

        $this->bank->bearerToken = base64_encode("{$client_id}:{$client_secret}");
    }

    function getAuthenticate()
    {
        $this->headers['Authorization'] = sprintf("Bearer %s",[$this->bank->bearerToken]);
        $this->headers['Content-Type'] = "application/x-www-form-urlencoded";
        $this->body['grant_type'] = 'client_credentials';
    }

    function getAccessToken()
    {
        $response = $this->client->request('POST', '/api/oauth/token', [
            'header' => $this->headers,
            'form_params' => $this->body,
            'verify' => false,
            'timeout' => 30,
        ]);

        return $response->getBody()->getContents();
    }

    function getIsotime()
    {}

    function getSignatureString($body = [])
    {
        $get_body = ($body) ? json_encode($body, JSON_UNESCAPED_SLASHED) : '';
        $hash_body = hash('sha256', $get_body);
    }

    function getSignature()
    {

    }
}
