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
        $params[TranzzoApi::P_REQ_RESULT_URL] = $this->url->link('checkout/success', '', true);
        $params[TranzzoApi::P_REQ_ORDER] = strval($order_id);
        $params[TranzzoApi::P_REQ_AMOUNT] = TranzzoApi::amountToDouble($amount);
        $params[TranzzoApi::P_REQ_CURRENCY] = $order_info['currency_code'];
        $params[TranzzoApi::P_REQ_DESCRIPTION] = "Order #{$order_id}";

        if(!empty($order_info['customer_id']))
            $params[TranzzoApi::P_REQ_CUSTOMER_ID] = strval($order_info['customer_id']);
        else
            $params[TranzzoApi::P_REQ_CUSTOMER_ID] = !empty($order_info['email'])? $order_info['email'] : 'unregistered';

        $params[TranzzoApi::P_REQ_CUSTOMER_EMAIL] = !empty($order_info['email']) ? $order_info['email'] : 'unregistered';

        if(!empty($order_info['firstname']))
            $params[TranzzoApi::P_REQ_CUSTOMER_FNAME] = $order_info['firstname'];

        if(!empty($order_info['lastname']))
            $params[TranzzoApi::P_REQ_CUSTOMER_LNAME] = $order_info['lastname'];

        if(!empty($order_info['telephone']))
            $params[TranzzoApi::P_REQ_CUSTOMER_PHONE] = $order_info['telephone'];

        $params[TranzzoApi::P_REQ_PRODUCTS] = array();

        $this->load->model('account/order');
        $products = $this->model_account_order->getOrderProducts($order_id);
        if(count($products) > 0) {
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
        $data['error'] = !empty($response['message'])? $response['message'] : '';
        $data['error'].= (!empty($response['args']) && is_array($response['args']))? ', args: ' . implode(', ', $response['args']) : '';

        if(!empty($response['redirect_url'])) {
            $data['redirect_url'] = $response['redirect_url'];
        }

        return $this->load->view('extension/payment/tranzzo', $data);
    }

    public function callback()
    {
        $this->load->library('tranzzoApi');

//        TranzzoApi::writeLog(array('$_GET' => $_GET, '$_POST' => $_POST,), 'data check', 'notif');

        $data = $_POST['data'];
        $signature = $_POST['signature'];
        if(empty($data) && empty($signature)) die('LOL! Bad Request!!!');

        $data_response = TranzzoApi::notificationDecode($data);
        $order_id = (int)$data_response[TranzzoApi::P_REQ_ORDER];
        if($this->tranzzoApi->validateSignature($data, $signature) && $order_id) {
            $this->load->language('extension/payment/tranzzo');
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            $amount_payment = TranzzoApi::amountToDouble($data_response[TranzzoApi::P_REQ_AMOUNT]);
            $amount_order = TranzzoApi::amountToDouble($order_info['total'] * $order_info['currency_value']);
            if ($data_response[TranzzoApi::P_RES_RESP_CODE] == 1000 && ($amount_payment == $amount_order)) {
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_complete_id'),
                    "{$this->language->get('text_pay_success')}\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_transaction')}: {$data_response[TranzzoApi::P_RES_TRSACT_ID]}
                       "
                );
            }
            else{
                $msg = !empty($data_response['response_code'])? "Response code: {$data_response['response_code']} ": "";
                $msg.= !empty($data_response['response_description'])? "Description: {$data_response['response_description']}": "";
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_failure_id'),
                    $msg
                );
            }
        }
    }
}