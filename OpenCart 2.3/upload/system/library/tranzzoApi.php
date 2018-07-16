<?php

final class TranzzoApi
{
    /*
     * https://tranzzo.docs.apiary.io/ The Tranzzo API is an HTTP API served by Tranzzo payment core
     */
    //Common params
    const P_MODE_HOSTED     = 'hosted';
    const P_MODE_DIRECT     = 'direct';
    const P_REQ_CPAY_ID     = 'uuid';
    const P_REQ_POS_ID      = 'pos_id';
    const P_REQ_ENDPOINT_KEY = 'key';
    const P_REQ_MODE        = 'mode';
    const P_REQ_METHOD      = 'method';
    const P_REQ_AMOUNT      = 'amount';
    const P_REQ_CURRENCY    = 'currency';
    const P_REQ_DESCRIPTION = 'description';
    const P_REQ_ORDER       = 'order_id';
    const P_REQ_PRODUCTS    = 'products';
    const P_REQ_ORDER_3DS_BYPASS   = 'order_3ds_bypass';
    const P_REQ_CC_NUMBER   = 'cc_number';
    const P_REQ_PAYWAY      = 'payway';

    const P_OPT_PAYLOAD     = 'payload';

    const P_REQ_CUSTOMER_ID     = 'customer_id';
    const P_REQ_CUSTOMER_EMAIL  = 'customer_email';
    const P_REQ_CUSTOMER_FNAME  = 'customer_fname';
    const P_REQ_CUSTOMER_LNAME  = 'customer_lname';
    const P_REQ_CUSTOMER_PHONE  = 'customer_phone';

    const P_REQ_SERVER_URL  = 'server_url';
    const P_REQ_RESULT_URL  = 'result_url';

    const P_REQ_SANDBOX     = 'sandbox';

    //Response params
    const P_RES_PROV_ORDER  = 'provider_order_id';
    const P_RES_PAYMENT_ID  = 'payment_id';
    const P_RES_TRSACT_ID   = 'transaction_id';
    const P_RES_STATUS      = 'status';
    const P_RES_CODE        = 'code';
    const P_RES_RESP_CODE   = 'response_code';
    const P_RES_RESP_DESC   = 'response_description';
    const P_RES_ORDER       = 'order_id';
    const P_RES_AMOUNT      = 'amount';
    const P_RES_CURRENCY    = 'currency';



    const P_TRZ_ST_SUCCESS      = 'success';
    const P_TRZ_ST_PENDING      = 'pending';
    const P_TRZ_ST_CANCEL       = 'rejected';
    const P_TRZ_ST_UNSUCCESSFUL = 'unsuccessful';
    const P_TRZ_ST_ANTIFRAUD    = 'antifraud';

    //Request method
    const R_METHOD_GET  = 'GET';
    const R_METHOD_POST = 'POST';

    //URI method
    const U_METHOD_PAYMENT = '/payment';
    const U_METHOD_POS = '/pos';



    /**
     * @var string
     */
    private $apiUrl = 'https://cpay.tranzzo.com/api/v1';

    /**
     * @var string
     */
    private $posId;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiSecret;

    /**
     * @var string
     */
    private $endpointsKey;

    /**
     * @var array $headers
     */
    private $headers;

    private $params = array();

    public function __construct($registry)
    {
        $this->posId = trim($this->config->get('tranzzo_pos_id'));
        $this->apiKey = trim($this->config->get('tranzzo_api_key'));
        $this->apiSecret = trim($this->config->get('tranzzo_api_secret'));
        $this->endpointsKey = trim($this->config->get('tranzzo_endpoints_key'));

        if(empty($this->posId) || empty($this->apiKey) || empty($this->apiSecret) || empty($this->endpointKey)){
            self::writeLog('Invalid constructor parameters', '', 'error');
        }
    }

    public function __get($name)
    {
        return $this->registry->get($name);
    }

    public function setServerUrl($value = '')
    {
        $this->params[self::P_REQ_SERVER_URL] = $value;
    }

    public function setResultUrl($value = '')
    {
        $this->params[self::P_REQ_RESULT_URL] = $value;
    }

    public function setOrderId($value = '')
    {
        $this->params[self::P_REQ_ORDER] = strval($value);
    }

    public function setAmount($value = 0, $round = null)
    {
        $this->params[self::P_REQ_AMOUNT] = self::amountToDouble($value, $round);
    }

    public function setCurrency($value = '')
    {
        $this->params[self::P_REQ_CURRENCY] = $value;
    }

    public function setDescription($value = '')
    {
        $this->params[self::P_REQ_DESCRIPTION] = !empty($value)? $value : 'Order payment';
    }

    public function setCustomerId($value = '')
    {
        $this->params[self::P_REQ_CUSTOMER_ID] = !empty($value)? strval($value) : 'unregistered';
    }

    public function setCustomerEmail($value = '')
    {
        $this->params[self::P_REQ_CUSTOMER_EMAIL] = !empty($value)? strval($value) : 'unregistered';
    }

    public function setCustomerFirstName($value = '')
    {
        if(!empty($value))
            $this->params[self::P_REQ_CUSTOMER_FNAME] = $value;
    }

    public function setCustomerLastName($value = '')
    {
        if(!empty($value))
            $this->params[self::P_REQ_CUSTOMER_LNAME] = $value;
    }

    public function setCustomerPhone($value = '')
    {
        if(!empty($value))
            $this->params[self::P_REQ_CUSTOMER_PHONE] = $value;
    }

    public function setProducts($value = array())
    {
        $this->params[self::P_REQ_PRODUCTS] = is_array($value)? $value : array();
    }

    public function addProduct($value = array())
    {
        if(is_array($value) && !empty($value))
            $this->params[self::P_REQ_PRODUCTS][] = $value;
    }

    /**
     * set custom value
     * @param string $value
     */
    public function setPayLoad($value = '')
    {
        $this->params[self::P_OPT_PAYLOAD] = $value;
    }

    /**
     * @return array
     */
    public function getReqParams()
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function createCreditPayment()
    {
        $this->params[self::P_REQ_METHOD] = 'credit';
        $this->params[self::P_REQ_POS_ID] = $this->posId;

        $uri = self::U_METHOD_PAYMENT;
        $this->setHeader('Content-Type:application/json');

        return $this->request(self::R_METHOD_POST, $uri);
    }

    /**
     * @return mixed
     */
    public function createPaymentHosted()
    {
        $this->params[self::P_REQ_POS_ID] = $this->posId;
        $this->params[self::P_REQ_MODE] = self::P_MODE_HOSTED;
        $this->params[self::P_REQ_METHOD] = 'purchase';
        $this->params[self::P_REQ_ORDER_3DS_BYPASS] = 'supported';

        $this->setHeader('Accept: application/json');
        $this->setHeader('Content-Type: application/json');

        return $this->request(self::R_METHOD_POST, self::U_METHOD_PAYMENT);
    }

    /**
     * @return mixed
     */
    public function checkPaymentStatus()
    {
        $uri = self::U_METHOD_POS. '/' . $this->posId . '/orders/' . $this->params[self::P_REQ_ORDER];

        return $this->request(self::R_METHOD_GET, $uri, []);
    }

    /**
     * @param $params
     * @return mixed
     */
    private function request($method, $uri, $params = null)
    {
        $url    = $this->apiUrl . $uri;
        $params = is_null($params)? $this->params : $params;
        $data   = json_encode($params);

        if(json_last_error()) {
            self::writeLog(json_last_error(), 'json_last_error', 'error');
            self::writeLog(json_last_error_msg(), 'json_last_error_msg', 'error');
        }

        $this->setHeader('X-API-Auth: CPAY '.$this->apiKey.':'.$this->apiSecret);
        $this->setHeader('X-API-KEY: ' . $this->endpointsKey);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if($method === self::R_METHOD_POST){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        $server_response = curl_exec($ch);
        $http_code = curl_getinfo($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        // for check request
//        self::writeLog($url, '', '', 0);
//        self::writeLog(array('headers' => $this->headers));
//        self::writeLog(array('params' => $params));
//
//        self::writeLog(array("httpcode" => $http_code, "errno" => $errno));
//        self::writeLog('response', $server_response);

        if(!$errno && empty($server_response))
            return $http_code;
        else
            return ((json_decode($server_response, true))? json_decode($server_response, true) : $server_response);
    }

    /**
     * @param $data
     * @param $requestSign
     * @return bool
     */
    public function validateSignature($data, $requestSign)
    {
        $signStr = $this->apiSecret . $data . $this->apiSecret;
        $sign = self::base64url_encode(sha1($signStr, true));

        if ($requestSign !== $sign) {
            return false;
        }

        return true;
    }

    /**
     * @param $params
     * @return string
     */
    private function createSign($params)
    {
        $json      = self::base64url_encode( json_encode($params) );
        $signature = $this->strToSign($this->apiSecret . $json . $this->apiSecret);
        return $signature;
    }

    /**
     * @param $str
     * @return string
     */
    private function strToSign($str)
    {
        return self::base64url_encode(sha1($str,1));
    }

    /**
     * @param $data
     * @return string
     */
    public static function base64url_encode($data)
    {
        return strtr(base64_encode($data), '+/', '-_');
    }
    /**
     * @param $data
     * @return bool|string
     */
    public static function base64url_decode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * @param $data
     * @return mixed
     */
    public static function parseDataResponse($data)
    {
        return json_decode(self::base64url_decode($data), true);
    }

    /**
     * @param $header
     */
    private function setHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * @param $key
     * @return mixed
     */
    private function getHeader($key)
    {
        return $this->headers[$key];
    }

    /**
     * @param string $value
     * @param int $round
     * @return float
     */
    static function amountToDouble($value = '', $round = null)
    {
        $val = floatval($value);
        return is_null($round)? round($val, 2) : round($value, (int)$round);
    }

    /**
     * @param $data
     * @param string $flag
     * @param string $filename
     * @param bool|true $append
     */
    static function writeLog($data, $flag = '', $filename = '', $append = true)
    {
        $filename = !empty($filename)? strval($filename) : basename(__FILE__);
        file_put_contents(__DIR__ . "/{$filename}.log", "\n\n" . date('H:i:s') . " - $flag \n" .
            (is_array($data)? json_encode($data, JSON_PRETTY_PRINT):$data)
            , ($append? FILE_APPEND : 0)
        );
    }
}