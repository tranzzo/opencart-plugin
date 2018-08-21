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
        $params[TranzzoApi::P_REQ_RESULT_URL] = $this->url->link('account/order/info&order_id='.$order_id, '', true);
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
        $this->cart->clear();
        return $this->load->view('extension/payment/tranzzo', $data);
    }

    public function callback()
    {
        $this->load->library('tranzzoApi');

//        TranzzoApi::writeLog(array('data', $_POST));

        if(empty($_POST['data']) || empty($_POST['signature'])) die('LOL! Bad Request!!!');
        $data = $_POST['data'];
        $signature = $_POST['signature'];
        $data_response = TranzzoApi::notificationDecode($data);

        // TranzzoApi::writeLog(array('callback', $data_response));
        $order_id = (int)$data_response[TranzzoApi::P_RES_PROV_ORDER];
        if($this->tranzzoApi->validateSignature($data, $signature) && $order_id) {
            $this->load->language('extension/payment/tranzzo');
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            $amount_payment = TranzzoApi::amountToDouble($data_response[TranzzoApi::P_REQ_AMOUNT]);
            $amount_order = TranzzoApi::amountToDouble($order_info['total'] * $order_info['currency_value']);
            if ($data_response[TranzzoApi::P_RES_RESP_CODE] == 1000 && ($amount_payment >= $amount_order)) {
                $order_info['payment_custom_field'] = $data_response;
                $this->model_checkout_order->editOrder($order_id,$order_info);
                $this->model_checkout_order->addOrderHistory(
                    $order_id,
                    $this->config->get('tranzzo_order_status_complete_id'),
                    "{$this->language->get('text_pay_success')}\n
                       {$this->language->get('text_payment_id')}: {$data_response[TranzzoApi::P_RES_PAYMENT_ID]}\n
                       {$this->language->get('text_transaction')}: {$data_response[TranzzoApi::P_RES_TRSACT_ID]}"
                );
            }
            elseif ($data_response['method'] == 'refund' && $data_response['status']=='success') {
                $this->model_checkout_order->addOrderHistory(
                  $order_id,
                  $this->config->get('tranzzo_order_status_complete_id'),
                  "{$this->language->get('text_pay_refund')}\n
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
    public function tranzzoRefund($route, &$args)
    {

        $order_id = (int)$args[0];
        $order_status = $args[1];

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $order_status_listen = $this->config->get('tranzzo_order_status_listen');
        if(!empty($order_info) && $order_info['payment_code'] && is_array($order_status_listen) && in_array($order_status, $order_status_listen)){
            if (!empty($order_info['payment_custom_field']['order_id'])) {
                $tranzzoOrderId = $order_info['payment_custom_field']['order_id'];
                $this->load->library('tranzzoApi');
                $amount = $order_info['total'] * $order_info['currency_value'];

                $data = [
                  'order_id' => strval($tranzzoOrderId),
                  'order_amount' => TranzzoApi::amountToDouble($amount),
                  'order_currency' => $order_info['currency_code'],
                  'refund_date' =>  date('Y-m-d H:i:s'),
                  'refund_amount' => TranzzoApi::amountToDouble(-$amount),
                  'server_url' => $this->url->link('extension/payment/tranzzo/callback', '', true),
                ];

                $response = $this->tranzzoApi->createRefund($data);
                if (!empty($response['message'])) {
                    throw new \Exception('Возврат не удался! ' . $response['message']);
                    return false;

                } else {
                    $refund_message = sprintf( ' Refunded %1$s, Tranzzo payment_id: %2$s', $amount, $response['payment_id']);
                    $args[2] .= $refund_message;
                }
            } else {
                $refund_message = sprintf( 'Refunds Tranzzo impossible, not enough data.');
                $args[2] .= $refund_message;
            }
        }
    }
}