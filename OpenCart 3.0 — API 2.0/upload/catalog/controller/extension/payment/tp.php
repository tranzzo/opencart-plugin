<?php

/**
 * Class ControllerExtensionPaymentTp
 */
class ControllerExtensionPaymentTp extends Controller
{
    /**
     * @var string
     */
    private $globalLabel = "TRANZZO";

    /**
     * @return mixed
     */
    public function index()
    {
        $isTestMode = $this->config->get('payment_tp_test_mode') != '0' ? 1 : 0;

        $this->load->language('extension/payment/tp');
        $data = $this->language->all();

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->load->library('ServiceApi');

        $order_id = $order_info['order_id'];

        $amount = $order_info['total'] * $order_info['currency_value'];

        $params = array();
        $params[ServiceApi::P_REQ_SERVER_URL] = $this->url->link('extension/payment/tp/callback', '', true);
        $params[ServiceApi::P_REQ_RESULT_URL] = $this->url->link('account/order/info&order_id=' . $order_id, '', true);
        $params[ServiceApi::P_REQ_ORDER] = strval($order_id);
        $params[ServiceApi::P_REQ_AMOUNT] = $isTestMode ? ServiceApi::amountToDouble(2) : ServiceApi::amountToDouble($amount);
        $params[ServiceApi::P_REQ_CURRENCY] = $isTestMode ? 'XTS' : $order_info['currency_code'];
        $params[ServiceApi::P_REQ_DESCRIPTION] = "Order #{$order_id}";

        if (!empty($order_info['customer_id']))
            $params[ServiceApi::P_REQ_CUSTOMER_ID] = strval($order_info['customer_id']);
        else
            $params[ServiceApi::P_REQ_CUSTOMER_ID] = !empty($order_info['email']) ? $order_info['email'] : 'unregistered';

        $params[ServiceApi::P_REQ_CUSTOMER_EMAIL] = !empty($order_info['email']) ? $order_info['email'] : 'unregistered';

        if (!empty($order_info['firstname']))
            $params[ServiceApi::P_REQ_CUSTOMER_FNAME] = $order_info['firstname'];

        if (!empty($order_info['lastname']))
            $params[ServiceApi::P_REQ_CUSTOMER_LNAME] = $order_info['lastname'];

        if (!empty($order_info['telephone']))
            $params[ServiceApi::P_REQ_CUSTOMER_PHONE] = $order_info['telephone'];

        $params[ServiceApi::P_REQ_PRODUCTS] = array();

        $this->load->model('account/order');
        $products = $this->model_account_order->getOrderProducts($order_id);

        if (count($products) > 0) {
            $items = array();
            $this->load->model('catalog/product');
            foreach ($products as $product) {
                $items[] = array(
                    'id' => strval($product['product_id']),
                    'name' => $product['name'],
                    'url' => $this->url->link('product/product&product_id=' . $product['product_id']),
                    'currency' => $isTestMode ? 'XTS' : $order_info['currency_code'],
                    'amount' => ServiceApi::amountToDouble($product['total'] * $order_info['currency_value']),
                    'qty' => intval($product['quantity']),
                );
            }

            $params[ServiceApi::P_REQ_PRODUCTS] = $items;
        }

        $response = $this->ServiceApi->createPaymentHosted($params);

        $data['action'] = $this->url->link('checkout/checkout', '', true);
        $data['error'] = !empty($response['message']) ? $response['message'] : '';
        $data['error'] .= (!empty($response['args']) && is_array($response['args'])) ? ', args: ' . urldecode(http_build_query($response['args'],'',', ')) : '';

        if (!empty($response['redirect_url'])) {
            $data['redirect_url'] = $response['redirect_url'];
        }

        $this->model_checkout_order->addOrderHistory(
            $order_id,
            1,
            'Pending',
            true
        );

        $this->cart->clear();

        return $this->load->view('extension/payment/tp', $data);
    }

    /**
     *
     */
    public function callback()
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }

        $isTestMode = $this->config->get('payment_tp_test_mode') != '0';

        $this->load->library('ServiceApi');

        $this->ServiceApi->writeLog('callback', '');

        if (empty($_POST['data']) || empty($_POST['signature'])) die('LOL! Bad Request!!!');
        $data = $_POST['data'];
        $signature = $_POST['signature'];
        $data_response = ServiceApi::notificationDecode($data);

        $this->ServiceApi->writeLog(array('$data_response', $data_response));

        $this->load->model('extension/payment/tp');

        if ($data_response['method'] == 'purchase' || $data_response['method'] == 'auth') {
            $order_id = (int)$data_response[ServiceApi::P_REQ_ORDER];
        } else {
            $res = $this->model_extension_payment_tp->getOrderId((int)$data_response['order_id']);
            $order_id = $res->row['order_id'];
        }

        $this->ServiceApi->writeLog(array('$order_id', $order_id));

        if ($this->ServiceApi->validateSignature($data, $signature) && $order_id) {
            $this->load->language('extension/payment/tp');
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            $amount_payment = ServiceApi::amountToDouble($data_response[ServiceApi::P_REQ_AMOUNT]);
            $amount_order = ServiceApi::amountToDouble($order_info['total'] * $order_info['currency_value']);


            if ($data_response[ServiceApi::P_RES_RESP_CODE] == 1000 && $data_response[ServiceApi::P_REQ_METHOD] == 'purchase') {

                $payment_data = [
                    'method' => $data_response[ServiceApi::P_REQ_METHOD],
                    'amount_payment' => $amount_payment,
                    'amount_order' => $amount_order,
                    'order_id' => $data_response[ServiceApi::P_REQ_ORDER],
                    'payment_id' => $data_response[ServiceApi::P_RES_PAYMENT_ID],
                    'status' => $data_response['status']
                ];

                $this->model_extension_payment_tp->addPaymentData($order_id, $payment_data);

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_order_status_complete_id'),
                    sprintf($this->language->get('text_pay_success'),$this->globalLabel, $amount_order) . "\n
                       {$this->language->get('text_payment_id')}: {$data_response[ServiceApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[ServiceApi::P_REQ_ORDER]}",
                    true
                );

                $this->model_extension_payment_tp->createTransaction($data_response[ServiceApi::P_REQ_METHOD], $amount_payment, $order_id);

            }
            elseif ($data_response[ServiceApi::P_RES_RESP_CODE] == 1002) {
                $payment_data = [
                    'method' => $data_response[ServiceApi::P_REQ_METHOD],
                    'amount_payment' => $amount_payment,
                    'amount_order' => $amount_order,
                    'order_id' => $data_response[ServiceApi::P_REQ_ORDER],
                    'payment_id' => $data_response[ServiceApi::P_RES_PAYMENT_ID],
                    'status' => $data_response['status']
                ];
                $this->model_extension_payment_tp->addPaymentData($order_id, $payment_data);

                $status_name = $this->model_extension_payment_tp->getStatusName($this->config->get('payment_tp_order_status_complete_id'), $this->config->get('config_language_id'));

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_order_status_auth_id'),
                    sprintf($this->language->get('text_pay_auth'), $this->globalLabel). "'\n
                       {$this->language->get('text_payment_id')}: {$data_response[ServiceApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[ServiceApi::P_REQ_ORDER]}",
                    true
                );

                $this->model_extension_payment_tp->createTransaction($data_response[ServiceApi::P_REQ_METHOD], $amount_payment, $order_id);
            }
            elseif ($data_response['method'] == 'refund' && $data_response['status'] == 'success') {
                $this->ServiceApi->writeLog(array('5', 1));

                $payment_data = [
                    'method' => 'refund',
                    'order_id' => $data_response[ServiceApi::P_REQ_ORDER],
                    'refund_amount' => $amount_payment,
                    'payment_id' => $data_response[ServiceApi::P_RES_PAYMENT_ID],
                    'status' => $data_response['status']
                ];

                $this->load->model('extension/payment/tp');
                $this->model_extension_payment_tp->addPaymentData($order_id, $payment_data);

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_order_status_listen'),
                    sprintf($this->language->get('text_pay_refund'), $this->globalLabel)."\n
                       {$this->language->get('text_payment_id')}: {$data_response[ServiceApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[ServiceApi::P_REQ_ORDER]}",
                    true
                );

                $this->model_extension_payment_tp->createTransaction($data_response[ServiceApi::P_REQ_METHOD], $amount_payment, $order_id);
            }
            elseif ($data_response['method'] == 'void' && $data_response['status'] == 'success') {
                $this->ServiceApi->writeLog(array('4', 1));

                $this->ServiceApi->writeLog('method void', '');

                $payment_data = [
                    'method' => 'void',
                    'order_id' => $data_response[ServiceApi::P_REQ_ORDER],
                    'refund_amount' => ServiceApi::amountToDouble($data_response['amount']),
                    'payment_id' => $data_response[ServiceApi::P_RES_PAYMENT_ID],
                    'status' => $data_response['status']
                ];

                $this->ServiceApi->writeLog(array('$void_data', $payment_data));

                $this->load->model('extension/payment/tp');
                $this->model_extension_payment_tp->addPaymentData($order_id, $payment_data);

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_custom_auth_voided_status'),
                    sprintf($this->language->get('text_pay_void'), $this->globalLabel) ."\n
                       {$this->language->get('text_payment_id')}: {$data_response[ServiceApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[ServiceApi::P_REQ_ORDER]}",
                    true
                );

                $this->model_extension_payment_tp->createTransaction($data_response[ServiceApi::P_REQ_METHOD], $amount_payment, $order_id);
            }
            elseif ($data_response['method'] == 'capture' && $data_response['status'] == 'success') {

                $this->ServiceApi->writeLog('method capture', '');

                $payment_data_old = (array)json_decode($this->model_extension_payment_tp->getPaymentData($order_id));

                $payment_data = [
                    'method' => 'capture',
                    'order_id' => $data_response[ServiceApi::P_REQ_ORDER],
                    'amount_payment' => ServiceApi::amountToDouble($data_response['amount']),
                    'amount_order_new' => $amount_order,
                    'amount_order' => ServiceApi::amountToDouble($payment_data_old['amount_order']),
                    'payment_id' => $data_response[ServiceApi::P_RES_PAYMENT_ID],
                    'status' => $data_response['status']
                ];

                $this->ServiceApi->writeLog(array('$capture_data', $payment_data));

                $this->load->model('extension/payment/tp');
                $this->model_extension_payment_tp->addPaymentData($order_id, $payment_data);

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_custom_auth_success_status'),
                    sprintf($this->language->get('text_pay_success'), $amount_order) . "\n
                       {$this->language->get('text_payment_id')}: {$data_response[ServiceApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[ServiceApi::P_REQ_ORDER]}",
                    true
                );

                $this->model_extension_payment_tp->createTransaction($data_response[ServiceApi::P_REQ_METHOD], $amount_payment, $order_id);
            }
            elseif($data_response['status'] == 'pending'){
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_custom_pending_status'),
                    'pending',
                    true
                );
            }
            else {
                $msg = !empty($data_response['response_code']) ? "Response code: {$data_response['response_code']} " : "";
                $msg .= !empty($data_response['response_description']) ? "Description: {$data_response['response_description']}" : "";
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('payment_tp_order_status_failure_id'),
                    $msg
                );
            }
        }
    }
}
