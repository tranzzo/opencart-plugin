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
    const P_REQ_BILL_ORDER       = 'billing_order_id';
    const P_REQ_PRODUCTS    = 'products';
    const P_REQ_ORDER_3DS_BYPASS   = 'order_3ds_bypass';
    const P_REQ_CC_NUMBER   = 'cc_number';
    const P_OPT_PAYLOAD     = 'payload';
    const P_REQ_PAYWAY     = 'payway';

    const P_REQ_CUSTOMER_ID = 'customer_id';
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
//    const P_RES_RESP_CODE   = 'response_code';
    const P_RES_RESP_CODE   = 'status_code';
    const P_RES_DESC        = 'code_description';

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
    const U_METHOD_REFUND = '/refund';
    //new
    const U_METHOD_VOID = '/void';
    const U_METHOD_CAPTURE = '/capture';



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

    //new
    private $type_payment;
    //new

    /**
     * @var array $headers
     */
    private $headers;

    public function __construct($registry=null)
    {
        $this->registry = $registry;

        $this->posId = trim( $this->config->get('payment_tranzzo_pos_id'));
        $this->apiKey = trim( $this->config->get('payment_tranzzo_api_key'));
        $this->apiSecret = trim( $this->config->get('payment_tranzzo_api_secret'));
        $this->endpointsKey = trim( $this->config->get('payment_tranzzo_endpoints_key'));

        //new
        $this->type_payment = ( $this->config->get('payment_tranzzo_type_payment') == '1') ? 1 : 0;
        //new

        if(empty($this->posId) || empty($this->apiKey) || empty($this->apiSecret) || empty($this->endpointsKey)){
            self::writeLog('Invalid constructor parameters');
        }
    }

    public function __get($name)
    {
        return $this->registry->get($name);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function createCreditPayment($params = array())
    {
        $params[self::P_REQ_METHOD] = 'credit';
        $params[self::P_REQ_POS_ID] = $this->posId;

        $uri = self::U_METHOD_PAYMENT;
        $this->setHeader('Content-Type:application/json');

        return $this->request($params, self::R_METHOD_POST, $uri);
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function createPaymentHosted($params = array())
    {
        $params[self::P_REQ_POS_ID] = $this->posId;
        $params[self::P_REQ_MODE] = self::P_MODE_HOSTED;
//        $params[self::P_REQ_METHOD] = 'purchase';
        $params[self::P_REQ_METHOD] = empty($this->type_payment) ? 'purchase' : 'auth';
        $params[self::P_REQ_ORDER_3DS_BYPASS] = 'supported';

        $this->setHeader('Accept: application/json');
        $this->setHeader('Content-Type: application/json');

        self::writeLog(array('createPaymentHosted $params'=>(array)$params));

        return $this->request($params, self::R_METHOD_POST, self::U_METHOD_PAYMENT);
    }

    /**
     * @param $params
     * @return mixed
     */
    public function checkPaymentStatus($params)
    {
        $uri = self::U_METHOD_POS. '/' . $this->posId . '/orders/' . $params[self::P_REQ_ORDER];

        return $this->request([], self::R_METHOD_GET, $uri);
    }

    /**
     * @param $params
     * @return mixed
     */
    private function request($params, $method, $uri)
    {
        //new
        //serialize_precision for json_encode
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }
        //new
        $url    = $this->apiUrl . $uri;
        $data   = json_encode($params);
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

        $server_output = curl_exec($ch);
        $http_code = curl_getinfo($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if(!$errno && empty($server_output))
            return $http_code;
        else
            return (json_decode($server_output, true))? json_decode($server_output, true) : $server_output;
    }

    /**
     * @param $data
     * @return mixed|null
     */
    public static function notificationDecode($data)
    {
        return json_decode(self::base64url_decode($data), true);
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
            self::writeLog('signature wrong!');
            return false;
        }

        return true;
    }
    /**
     * @param $params
     * @return mixed
     */
    public function createRefund($params = array())
    {
        $params[self::P_REQ_POS_ID] = $this->posId;

        $this->setHeader('Accept: application/json');
        $this->setHeader('Content-Type: application/json');

        return $this->request($params, self::R_METHOD_POST, self::U_METHOD_REFUND);
    }

    //new
    public function createVoid($params = array())
    {
        $params[self::P_REQ_POS_ID] = $this->posId;

        $this->setHeader('Accept: application/json');
        $this->setHeader('Content-Type: application/json');

        return $this->request($params, self::R_METHOD_POST, self::U_METHOD_VOID);
    }

    public function createCapture($params = array())
    {
        self::writeLog('createCapture');
        $params[self::P_REQ_POS_ID] = $this->posId;

        $this->setHeader('Accept: application/json');
        $this->setHeader('Content-Type: application/json');

        return $this->request($params, self::R_METHOD_POST, self::U_METHOD_CAPTURE);
    }
    //new

    //
    public function getTypeMethod(){
        return $this->type_payment;
    }
    //


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
        return is_null($round)? $val : round($value, (int)$round);
    }

    static function writeLog($data, $flag = '', $filename = '', $append = true)
    {
        $filename = !empty($filename)? strval($filename) : basename(__FILE__);
        file_put_contents(__DIR__ . "/{$filename}.log", "\n\n" . date('H:i:s') . " - $flag \n" .
            (is_array($data)? json_encode($data, JSON_PRETTY_PRINT):$data)
            , ($append? FILE_APPEND : 0)
        );
    }
}

/*
 * изменения АПИ 2.0
 * транзоАпи:
 * добавить const P_REQ_BILL_ORDER       = 'billing_order_id';
 * изменить значение const P_RES_RESP_CODE   = 'status_code';
 *
 * каталог/контроллер:
 * заменить константу $order_id = (int)$data_response[TranzzoApi::P_REQ_ORDER];
 * добавить для кода=1000 && $data_response[TranzzoApi::P_REQ_METHOD] == 'purchase'
 * заменить константу для иф purchase в $payment_data и addOrderHistory P_REQ_ORDER на P_REQ_BILL_ORDER
 * заменить константу для иф 1002 в $payment_data и addOrderHistory P_REQ_ORDER на P_REQ_BILL_ORDER
 */