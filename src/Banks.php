<?php

namespace qlixes\Bank;

use stdClass;
use qlixes\Bank\BanksException;
use GuzzleHttp\Client;
use Carbon\Carbon;

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

        $this->options['verify'] = false;
        $this->options['timeout'] = 30;

        $this->options['headers'] = $this->headers;
    }

    function getBaseUri($baseUrl)
    {
        $client = new Client([
            'base_uri' => $baseUrl
        ]);
    }

    /**
     * Generate ISO8601 Time.
     *
     * @param string $timeZone Time yang akan dipergunakan
     *
     * @return string
     */
    public static function generateIsoTime()
    {
        $date = Carbon::now(self::getTimeZone());
        date_default_timezone_set(self::getTimeZone());
        $fmt     = $date->format('Y-m-d\TH:i:s');
        $ISO8601 = sprintf("$fmt.%s%s", substr(microtime(), 2, 3), date('P'));

        return $ISO8601;
    }

    /**
     * Validasi jika clientsecret telah di-definsikan.
     *
     * @param array $sourceAccountId
     *
     * @return bool
     */
    private function validateArray($sourceAccountId = [])
    {
        if (empty($sourceAccountId)) {
            throw new BanksException('AccountNumber tidak boleh kosong.');
        } else {
            if (count($sourceAccountId) > 20) {
                throw new BanksException('Maksimal Account Number ' . 20);
            }
        }

        return true;
    }
    
    /**
     * Implode an array with the key and value pair giving
     * a glue, a separator between pairs and the array
     * to implode.
     *
     * @param string $glue      The glue between key and value
     * @param string $separator Separator between pairs
     * @param array  $array     The array to implode
     *
     * @return string The imploded array
     */
    public static function arrayImplode($glue, $separator, $array)
    {
        if (!is_array($array)) {
            throw new BanksException('Data harus array.');
        }
        $string = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $string[] = "{$key}{$glue}{$val}";
        }

        return implode($separator, $string);
    }
}