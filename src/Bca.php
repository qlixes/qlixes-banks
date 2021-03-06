<?php

namespace qlixes\Bank;

use qlixes\Bank\Banks;
use qlixes\Bank\BanksException;

/**
 * BCA REST API Library.
 *
 * @author     Pribumi Technology
 * @license    MIT
 * @copyright  (c) 2017, Pribumi Technology
 */
class Bca extends Banks
{    
    public static $VERSION = '2.2.0';

    private static $timezone = 'Asia/Jakarta';

    private static $port = 443;

    private static $hostName = 'sandbox.bca.co.id';

    protected $settings = array(
        'corp_id'       => '',
        'client_id'     => '',
        'client_secret' => '',
        'api_key'       => '',
        'secret_key'    => '',
        'scheme'        => 'https',
        'port'          => 443,
        'timezone'      => 'Asia/Jakarta',
        'timeout'       => null,
        'development'   => true,
    );

    /**
     * Default Constructor.
     *
     * @param string $corp_id nilai corp id
     * @param string $client_id nilai client key
     * @param string $client_secret nilai client secret
     * @param string $api_key niali oauth key
     * @param string $secret_key nilai oauth secret
     * @param array $options opsi ke server bca
     */
    public function __construct($corp_id, $client_id, $client_secret, $api_key, $secret_key, $options = array())
    {
        if (!isset($options['port'])) {
            $options['port'] = self::getPort();
        }

        if (!isset($options['timezone'])) {
            $options['timezone'] = self::getTimeZone();
        }

        foreach ($options as $key => $value) {
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }

        if (!array_key_exists('host', $this->settings)) {
            if (array_key_exists('host', $options)) {
                $this->settings['host'] = $options['host'];
            } else {
                $this->settings['host'] = self::getHostName();
            }
        }

        $this->settings['corp_id']       = $corp_id;
        $this->settings['client_id']     = $client_id;
        $this->settings['client_secret'] = $client_secret;
        $this->settings['api_key']       = $api_key;
        $this->settings['secret_key']    = $secret_key;
        
        $this->settings['host'] =
            preg_replace('/http[s]?\:\/\//', '', $this->settings['host'], 1);

        $this->client = $this->getBaseUri($this->ddnDomain());

        $this->headers['Accept'] = 'application/json';
    }

    /**
     * Ambil Nilai settings.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Build the ddn domain.
     * output = 'https://sandbox.bca.co.id:443'
     * scheme = http(s)
     * host = sandbox.bca.co.id
     * port = 80 ? 443
     *
     * @return string
     */
    private function ddnDomain()
    {
        return $this->settings['scheme'] . '://' . $this->settings['host'] . ':' . $this->settings['port'] . '/';
    }

    /**
     * Generate authentifikasi ke server berupa OAUTH.
     *
     * @return \Unirest\Response
     */
    public function httpAuth()
    {
        $client_id     = $this->settings['client_id'];
        $client_secret = $this->settings['client_secret'];
        
        $headerToken = base64_encode("$client_id:$client_secret");

        $this->headers['Authorization'] = "Basic $headerToken";

        $request_path = "/api/oauth/token";

        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;

        $response = $this->client->request('POST', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Ambil informasi saldo berdasarkan nomor akun BCA.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     * @param array $sourceAccountId nomor akun yang akan dicek
     *
     * @return \Unirest\Response
     */
    public function getBalanceInfo($oauth_token, $sourceAccountId = [])
    {
        $corp_id = $this->settings['corp_id'];
        $apikey  = $this->settings['api_key'];
        $secret  = $this->settings['secret_key'];
        
        $this->validateArray($sourceAccountId);

        ksort($sourceAccountId);
        $arraySplit = implode(",", $sourceAccountId);
        $arraySplit = urlencode($arraySplit);

        $uriSign       = "GET:/banking/v3/corporates/$corp_id/accounts/$arraySplit";
        $isoTime       = self::generateIsoTime();
        $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, null);

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;
        $this->headers['X-BCA-Signature'] = $authSignature;

        $request_path = "/banking/v3/corporates/$corp_id/accounts/$arraySplit";
        
        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;

        $response= $this->client->request('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Ambil Daftar transaksi pertanggal.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     * @param array $sourceAccount nomor akun yang akan dicek
     * @param string $startDate tanggal awal
     * @param string $endDate tanggal akhir
     * @param string $corp_id nilai CorporateID yang telah diberikan oleh pihak BCA
     *
     * @return \Unirest\Response
     */
    public function getAccountStatement($oauth_token, $sourceAccount, $startDate, $endDate)
    {
        $corp_id = $this->settings['corp_id'];

        $apikey = $this->settings['api_key'];

        $secret = $this->settings['secret_key'];

        $uriSign       = "GET:/banking/v3/corporates/$corp_id/accounts/$sourceAccount/statements?EndDate=$endDate&StartDate=$startDate";
        $isoTime       = self::generateIsoTime();
        $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, null);

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;
        $this->headers['X-BCA-Signature'] = $authSignature;

        $request_path = "/banking/v3/corporates/$corp_id/accounts/$sourceAccount/statements?EndDate=$endDate&StartDate=$startDate";

        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;

        $response = $this->client->request('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Ambil informasi ATM berdasarkan lokasi GEO.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     * @param string $latitude Langitude GPS
     * @param string $longitude Longitude GPS
     * @param string $count Jumlah ATM BCA yang akan ditampilkan
     * @param string $radius Nilai radius dari lokasi GEO
     *
     * @return \Unirest\Response
     */
    public function getAtmLocation(
        $oauth_token,
        $latitude,
        $longitude,
        $count = '10',
        $radius = '20'
    ) {
        $apikey = $this->settings['api_key'];
        
        $secret = $this->settings['secret_key'];

        $params              = array();
        $params['SearchBy']  = 'Distance';
        $params['Latitude']  = $latitude;
        $params['Longitude'] = $longitude;
        $params['Count']     = $count;
        $params['Radius']    = $radius;
        ksort($params);

        $auth_query_string = self::arrayImplode('=', '&', $params);

        $uriSign       = "GET:/general/info-bca/atm?$auth_query_string";
        $isoTime       = self::generateIsoTime();
        $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, null);

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;
        $this->headers['X-BCA-Signature'] = $authSignature;

        $request_path = "/general/info-bca/atm?SearchBy=Distance&Latitude=$latitude&Longitude=$longitude&Count=$count&Radius=$radius";

        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;

        $response = $this->client->request('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Ambil KURS mata uang.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     * @param string $rateType type rate
     * @param string $currency Mata uang
     *
     * @return \Unirest\Response
     */
    public function getForexRate(
        $oauth_token,
        $rateType = 'e-rate',
        $currency = 'USD'
    ) {
        $apikey = $this->settings['api_key'];

        $secret = $this->settings['secret_key'];

        $params             = array();
        $params['RateType'] = strtolower($rateType);
        $params['Currency'] = strtoupper($currency);
        ksort($params);

        $auth_query_string = self::arrayImplode('=', '&', $params);

        $uriSign       = "GET:/general/rate/forex?$auth_query_string";
        $isoTime       = self::generateIsoTime();
        $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, null);

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;
        $this->headers['X-BCA-Signature'] = $authSignature;

        $request_path = "/general/rate/forex?$auth_query_string";

        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;
        
        $response = $this->client->request('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Transfer dana kepada akun lain dengan jumlah nominal tertentu.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     * @param int $amount nilai dana dalam RUPIAH yang akan ditransfer, Format: 13.2
     * @param string $beneficiaryAccountNumber  BCA Account number to be credited (Destination)
     * @param string $referenceID Sender's transaction reference ID
     * @param string $remark1 Transfer remark for receiver
     * @param string $remark2 ransfer remark for receiver
     * @param string $sourceAccountNumber Source of Fund Account Number
     * @param string $transactionID Transcation ID unique per day (using UTC+07 Time Zone). Format: Number
     * @param string $corp_id nilai CorporateID yang telah diberikan oleh pihak BCA [Optional]
     *
     * @return \Unirest\Response
     */
    public function fundTransfers(
        $oauth_token,
        $amount,
        $sourceAccountNumber,
        $beneficiaryAccountNumber,
        $referenceID,
        $remark1,
        $remark2,
        $transactionID
    ) {
        $corp_id = $this->settings['corp_id'];
        $apikey = $this->settings['api_key'];
        $secret = $this->settings['secret_key'];

        $uriSign = "POST:/banking/corporates/transfers";
        
        $isoTime = self::generateIsoTime();

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;

        $request_path = "/banking/corporates/transfers";

        $bodyData                             = array();
        $bodyData['Amount']                   = $amount;
        $bodyData['BeneficiaryAccountNumber'] = strtolower(str_replace(' ', '', $beneficiaryAccountNumber));
        $bodyData['CorporateID']              = strtolower(str_replace(' ', '', $corp_id));
        $bodyData['CurrencyCode']             = 'idr';
        $bodyData['ReferenceID']              = strtolower(str_replace(' ', '', $referenceID));
        $bodyData['Remark1']                  = strtolower(str_replace(' ', '', $remark1));
        $bodyData['Remark2']                  = strtolower(str_replace(' ', '', $remark2));
        $bodyData['SourceAccountNumber']      = strtolower(str_replace(' ', '', $sourceAccountNumber));
        $bodyData['TransactionDate']          = $isoTime;
        $bodyData['TransactionID']            = strtolower(str_replace(' ', '', $transactionID));

        // Harus disort agar mudah kalkulasi HMAC
        ksort($bodyData);

        $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, $bodyData);

        $headers['X-BCA-Signature'] = $authSignature;

        $this->options['form_params'] = $bodyData;

        $response = $this->client->response('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Realtime deposit untuk produk BCA.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     *
     * @return \Unirest\Response
     */
    public function getDepositRate($oauth_token)
    {
        $apikey  = $this->settings['api_key'];
        $secret  = $this->settings['secret_key'];

        $uriSign       = "GET:/general/rate/deposit";
        $isoTime       = self::generateIsoTime();
        $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, null);

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;
        $this->headers['X-BCA-Signature'] = $authSignature;

        $request_path = "/general/rate/deposit";

        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;

        $response = $this->client->response('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Realtime deposit untuk produk BCA.
     *
     * @param string $oauth_token nilai token yang telah didapatkan setelah login
     *
     * @return \Unirest\Response
     */
    public function getInquiryPaymentStatus(
        $oauth_token,
        $company_code,
        $request_id = null,
        $customer_no = null
    ) {
        $apikey  = $this->settings['api_key'];

        $secret  = $this->settings['secret_key'];

        $params             = array();
        $params['CompanyCode'] = $company_code;
        $params['RequestID'] = $request_id;
        $params['CustomerNumber'] = $customer_no;
        ksort($params);

        $auth_query_string = self::arrayImplode('=', '&', $params);

        // $uriSign       = "GET:/general/rate/deposit";
        // $isoTime       = self::generateIsoTime();
        // $authSignature = self::generateSign($uriSign, $oauth_token, $secret, $isoTime, null);

        $this->headers['Content-Type']    = 'application/json';
        $this->headers['Authorization']   = "Bearer $oauth_token";
        $this->headers['X-BCA-Key']       = $apikey;
        $this->headers['X-BCA-Timestamp'] = $isoTime;
        $this->headers['X-BCA-Signature'] = $authSignature;

        $request_path = "/va/payments?$auth_query_string";

        $body['grant_type'] = 'client_credentials';
        $this->options['form_params'] = $body;

        $response = $this->client->response('GET', $request_path, $this->options);

        return $response->getBody()->getContents();
    }

    /**
     * Generate Signature.
     *
     * @param string $url Url yang akan disign.
     * @param string $auth_token string nilai token dari login.
     * @param string $secret_key string secretkey yang telah diberikan oleh BCA.
     * @param string $isoTime string Waktu ISO8601.
     * @param array $bodyToHash array Body yang akan dikirimkan ke Server BCA.
     *
     * @return string
     */
    public static function generateSign($url, $auth_token, $secret_key, $isoTime, $bodyToHash)
    {
        $hash = null;
        if (is_array($bodyToHash)) {
            ksort($bodyToHash);
            $encoderData = json_encode($bodyToHash, JSON_UNESCAPED_SLASHES);
            $hash        = hash("sha256", $encoderData);
        } else {
            $hash = hash("sha256", "");
        }
        $stringToSign   = $url . ":" . $auth_token . ":" . $hash . ":" . $isoTime;
        $auth_signature = hash_hmac('sha256', $stringToSign, $secret_key, false);

        return $auth_signature;
    }

    /**
     * Set TimeZone.
     *
     * @param string $timeZone Time yang akan dipergunakan.
     *
     * @return string
     */
    public static function setTimeZone($timeZone)
    {
        self::$timezone = $timeZone;
    }

    /**
     * Get TimeZone.
     *
     * @return string
     */
    public static function getTimeZone()
    {
        return self::$timezone;
    }

    /**
     * Set nama domain BCA yang akan dipergunakan.
     *
     * @param string $hostName nama domain BCA yang akan dipergunakan.
     *
     * @return string
     */
    public static function setHostName($hostName)
    {
        self::$hostName = $hostName;
    }

    /**
     * Ambil nama domain BCA yang akan dipergunakan.
     *
     * @return string
     */
    public static function getHostName()
    {
        return self::$hostName;
    }

    /**
     * Set BCA port
     *
     * @param int $port Port yang akan dipergunakan
     *
     * @return int
     */
    public static function setPort($port)
    {
        self::$port = $port;
    }

    /**
     * Get BCA port
     *
     * @return int
     */
    public static function getPort()
    {
        return self::$port;
    }
}
