<?php

class ControllerExtensionPaymentTranzzo extends Controller
{
    public function index()
    {
        $this->load->language('extension/payment/tranzzo');
        $data = $this->language->all();

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        $this->load->library('tranzzoApi');

        $order_id = $order_info['order_id'];

        $amount = $order_info['total'] * $order_info['currency_value'];

        $params = array();
        $params[TranzzoApi::P_REQ_SERVER_URL] = $this->url->link('extension/payment/tranzzo/callback', '', true);
        $params[TranzzoApi::P_REQ_RESULT_URL] = $this->url->link('account/order/info&order_id=' . $order_id, '', true);
        $params[TranzzoApi::P_REQ_ORDER] = strval($order_id);
        $params[TranzzoApi::P_REQ_AMOUNT] = TranzzoApi::amountToDouble($amount);
        $params[TranzzoApi::P_REQ_CURRENCY] = $order_info['currency_code'];
        $params[TranzzoApi::P_REQ_DESCRIPTION] = "Order #{$order_id}";

        if (!empty($order_info['customer_id']))
            $params[TranzzoApi::P_REQ_CUSTOMER_ID] = strval($order_info['customer_id']);
        else
            $params[TranzzoApi::P_REQ_CUSTOMER_ID] = !empty($order_info['email']) ? $order_info['email'] : 'unregistered';

        $params[TranzzoApi::P_REQ_CUSTOMER_EMAIL] = !empty($order_info['email']) ? $order_info['email'] : 'unregistered';

        if (!empty($order_info['firstname']))
            $params[TranzzoApi::P_REQ_CUSTOMER_FNAME] = $order_info['firstname'];

        if (!empty($order_info['lastname']))
            $params[TranzzoApi::P_REQ_CUSTOMER_LNAME] = $order_info['lastname'];

        if (!empty($order_info['telephone']))
            $params[TranzzoApi::P_REQ_CUSTOMER_PHONE] = $order_info['telephone'];

        $params[TranzzoApi::P_REQ_PRODUCTS] = array();

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
                    'currency' => $order_info['currency_code'],
                    'amount' => TranzzoApi::amountToDouble($product['total'] * $order_info['currency_value']),
//                            'price_type' => 'gross', // net | gross
//                            'vat' => 0, // НДС
                    'qty' => intval($product['quantity']),
//                            'entity_id' => '',
                );
            }

            $params[TranzzoApi::P_REQ_PRODUCTS] = $items;
        }

        $response = $this->tranzzoApi->createPaymentHosted($params);

        $data['action'] = $this->url->link('checkout/checkout', '', true);
        $data['error'] = !empty($response['message']) ? $response['message'] : '';
        //$data['error'] .= (!empty($response['args']) && is_array($response['args'])) ? ', args: ' . implode(', ', $response['args']) : '';


        $this->tranzzoApi->writeLog(array('$response', $response));
        $data['error'] .= (!empty($response['args']['obj'][0]['msg']) && is_array($response['args']['obj'][0]['msg'])) ? ', mes: ' . implode(', ', $response['args']['obj'][0]['msg']) : '';


        if (!empty($response['redirect_url'])) {
            $data['redirect_url'] = $response['redirect_url'];
        }

        $this->cart->clear();
        return $this->load->view('extension/payment/tranzzo', $data);
    }

    public function callback()
    {
        //new
        //serialize_precision for json_encode
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }
        //new

        $this->load->library('tranzzoApi');

        $this->tranzzoApi->writeLog('callback', '');

        if (empty($_POST['data']) || empty($_POST['signature'])) die('LOL! Bad Request!!!');
        $data = $_POST['data'];
        $signature = $_POST['signature'];
        $data_response = TranzzoApi::notificationDecode($data);

        $this->tranzzoApi->writeLog(array('$data_response', $data_response));

        $this->load->model('extension/payment/tranzzo');

        //new
        if ($data_response['method'] == 'purchase' || $data_response['method'] == 'auth') {
            $order_id = (int)$data_response[TranzzoApi::P_REQ_ORDER];//[TranzzoApi::P_RES_PROV_ORDER];
        } else {
            $res = $this->model_extension_payment_tranzzo->getOrderId((int)$data_response['order_id']);
            $order_id = $res->row['order_id'];
        }
        //new
        $this->tranzzoApi->writeLog(array('$order_id', $order_id));

        if ($this->tranzzoApi->validateSignature($data, $signature) && $order_id) {
            $this->load->language('extension/payment/tranzzo');
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            $amount_payment = TranzzoApi::amountToDouble($data_response[TranzzoApi::P_REQ_AMOUNT]);
            $amount_order = TranzzoApi::amountToDouble($order_info['total'] * $order_info['currency_value']);


            if ($data_response[TranzzoApi::P_RES_RESP_CODE] == 1000 && $data_response[TranzzoApi::P_REQ_METHOD] == 'purchase') {
                // new
                $payment_data = [
                    'method' => $data_response[TranzzoApi::P_REQ_METHOD],
                    'amount_payment' => $amount_payment,
                    'amount_order' => $amount_order,
                    'order_id' => $data_response[TranzzoApi::P_REQ_BILL_ORDER]
                ];

                $this->model_extension_payment_tranzzo->addPaymentData($order_id, $payment_data);
                // new
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_complete_id'),
//                    "{$this->language->get('text_pay_success')}\n
                    sprintf($this->language->get('text_pay_success'), $amount_order) . "\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[TranzzoApi::P_REQ_BILL_ORDER]}",
                    true
                );
            } // new
            elseif ($data_response[TranzzoApi::P_RES_RESP_CODE] == 1002) {
                $payment_data = [
                    'method' => $data_response[TranzzoApi::P_REQ_METHOD],
                    'amount_payment' => $amount_payment,//
                    'amount_order' => $amount_order,
                    'order_id' => $data_response[TranzzoApi::P_REQ_BILL_ORDER],
                ];
                $this->model_extension_payment_tranzzo->addPaymentData($order_id, $payment_data);

                $status_name = $this->model_extension_payment_tranzzo->getStatusName($this->config->get('tranzzo_order_status_complete_id'), $this->config->get('config_language_id'));

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_auth_id'),
                    "{$this->language->get('text_pay_auth')}'" . $status_name->row['name'] . "'\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[TranzzoApi::P_REQ_BILL_ORDER]}",
                    true
                );
            } // new
            elseif ($data_response['method'] == 'refund' && $data_response['status'] == 'success') {

                //new
                $payment_data = [
                    'method' => 'refund',
                    'order_id' => $data_response[TranzzoApi::P_REQ_ORDER],
                    'refund_amount' => $amount_payment
                ];

                $this->load->model('extension/payment/tranzzo');
                $this->model_extension_payment_tranzzo->addPaymentData($order_id, $payment_data);
                //new

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    '13',//Полный возврат!! если выбран один статус для возвоата идет отправка эмейла. если более нет
                    "{$this->language->get('text_pay_refund')}\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[TranzzoApi::P_REQ_ORDER]}",
                    true
                );
            } //new
            elseif ($data_response['method'] == 'void' && $data_response['status'] == 'success') {

                $this->tranzzoApi->writeLog('method void', '');

                $payment_data = [
                    'method' => 'void',
                    'order_id' => $data_response[TranzzoApi::P_REQ_ORDER],
                    'refund_amount' => TranzzoApi::amountToDouble($data_response['amount'])
                ];

                $this->tranzzoApi->writeLog(array('$void_data', $payment_data));

                $this->load->model('extension/payment/tranzzo');
                $this->model_extension_payment_tranzzo->addPaymentData($order_id, $payment_data);

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    7, // Отменено !!!!!!!!!!!!!!!!! получить статус возврта
                    "{$this->language->get('text_pay_void')}\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[TranzzoApi::P_REQ_ORDER]}",
                    true
                );
            } elseif ($data_response['method'] == 'capture' && $data_response['status'] == 'success') {

                $this->tranzzoApi->writeLog('method capture', '');

                $payment_data_old = (array)json_decode($this->model_extension_payment_tranzzo->getPaymentData($order_id));

                $payment_data = [
                    'method' => 'capture',
                    'order_id' => $data_response[TranzzoApi::P_REQ_ORDER],
                    'amount_payment' => TranzzoApi::amountToDouble($data_response['amount']),
                    'amount_order_new' => $amount_order,
                    'amount_order' => TranzzoApi::amountToDouble($payment_data_old['amount_order'])
                ];

                $this->tranzzoApi->writeLog(array('$capture_data', $payment_data));

                $this->load->model('extension/payment/tranzzo');
                $this->model_extension_payment_tranzzo->addPaymentData($order_id, $payment_data);

                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_complete_id'),
                    sprintf($this->language->get('text_pay_success'), $amount_order) . "\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_order')}: {$data_response[TranzzoApi::P_REQ_ORDER]}",
                    true
                );
            }//new
            elseif($data_response['status'] == 'pending'){

            }
            else {
                $msg = !empty($data_response['response_code']) ? "Response code: {$data_response['response_code']} " : "";
                $msg .= !empty($data_response['response_description']) ? "Description: {$data_response['response_description']}" : "";
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_failure_id'),
                    $msg
                );
            }
        }
    }

    public function tranzzoRefund($route, &$args)
    {
        //new
        //serialize_precision for json_encode
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }
        //new

        $this->load->library('tranzzoApi');
        $this->tranzzoApi->writeLog(array('$args', $args));


        $order_id = (int)$args[0];
        $order_status = $args[1];

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);

        $order_status_listen = $this->config->get('tranzzo_order_status_listen');

        if (!empty($order_info) && $order_info['payment_code']) {

            //new
            $this->load->model('extension/payment/tranzzo');
            $payment_data = (array)json_decode($this->model_extension_payment_tranzzo->getPaymentData($order_id));
            $this->tranzzoApi->writeLog(array('$payment_data', $payment_data));
            //new

            if (!empty($payment_data)) {
                //new capture
                $order_status_for_capture = $this->config->get('tranzzo_order_status_complete_id');

                if ($order_status == $order_status_for_capture && !empty($payment_data['order_id']) && $payment_data['method'] == 'auth') {
                    $tranzzoOrderId = $payment_data['order_id'];

                    $orderAmountNew = $order_info['total'] * $order_info['currency_value']; //сумма заказа
                    $orderAmountOld = $payment_data['amount_order'] * $order_info['currency_value']; //сумма платежа без комиссии
                    $tranzzoAmount = $payment_data['amount_payment'] * $order_info['currency_value']; //сумма платежа с комиссией

                    $data = [
                        'order_id' => strval($tranzzoOrderId),
                        'order_currency' => $order_info['currency_code'],
                        'order_amount' => TranzzoApi::amountToDouble($tranzzoAmount), // сумма платежа
                        'server_url' => $this->url->link('extension/payment/tranzzo/callback', '', true),
                    ];
                    $this->tranzzoApi->writeLog(array('$response return', $data));

                    if ($orderAmountOld >= $orderAmountNew)
                        $data['change_amount'] = TranzzoApi::amountToDouble($orderAmountNew);// сумма по заказу
                    else {
                        throw new \Exception('Захват не удался! Сумма заказа превышает сумму платежа');
                        return false;
                    }

                    $this->tranzzoApi->writeLog(array('capture $data', $data));

                    if ($payment_data['method'] == 'auth') {
                        $response = $this->tranzzoApi->createCapture($data);

                        $this->tranzzoApi->writeLog(array('$response return', $response));

                        if (!empty($response['message'])) {
                            throw new \Exception('Захват не удался! ' . $response['message']);
                            return false;

                        }
                    }
                } // refund
                elseif (is_array($order_status_listen) && in_array($order_status, $order_status_listen)) {

                    if (!empty($payment_data['order_id'])) {
                        $tranzzoOrderId = $payment_data['order_id'];

                        $amount = $order_info['total'] * $order_info['currency_value'];
                        $this->tranzzoApi->writeLog(array('$amount$amount', $amount));

                        //new
                        $tranzzoAmount = $payment_data['amount_payment'] * $order_info['currency_value']; // сумма блокировки с комиссией
                        //new

                        $data = [
                            'order_id' => strval($tranzzoOrderId),
                            'order_currency' => $order_info['currency_code'],
                            //new
                            'order_amount' => TranzzoApi::amountToDouble($tranzzoAmount), // сумма заказа
                            //new
                            'server_url' => $this->url->link('extension/payment/tranzzo/callback', '', true),
                        ];

                        if ($payment_data['method'] == 'auth') {// auth method
                            $response = $this->tranzzoApi->createVoid($data);
                        } else {
                            if ($payment_data['amount_order'] >= $order_info['total']) {
                                if ($payment_data['method'] == 'capture')
                                    $data['refund_amount'] = TranzzoApi::amountToDouble($amount);
                            } else {
                                throw new \Exception('Возврат не удался! Сумма заказа превышает сумму платежа');
                                return false;
                            }
                            $response = $this->tranzzoApi->createRefund($data);
                        }

//                        $this->tranzzoApi->writeLog(array('$response return', $response));

                        if (!empty($response['message'])) {
                            throw new \Exception('Возврат не удался! ' . $response['message']);
                            return false;

                        } else {
                            $refund_message = sprintf(' Refunded %1$s, Tranzzo order_id: %2$s', $amount, $response['order_id']);

                            if (empty($args[2])) $args[2] = '';
                            $args[2] .= $refund_message;
                        }
                    } else {
                        $refund_message = sprintf('Refunds Tranzzo impossible, not enough data.');
                        $args[2] .= $refund_message;
                    }
                }
            }
        }
    }
}