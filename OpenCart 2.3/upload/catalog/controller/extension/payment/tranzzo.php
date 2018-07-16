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

        $this->tranzzoApi->setServerUrl($this->url->link('extension/payment/tranzzo/callback', '', true));
        $this->tranzzoApi->setResultUrl($this->url->link('checkout/success', '', true));
        $this->tranzzoApi->setOrderId($order_id);
        $this->tranzzoApi->setAmount($amount);
        $this->tranzzoApi->setCurrency($order_info['currency_code']);
        $this->tranzzoApi->setDescription("Order #{$order_id}");

        if(!empty($order_info['customer_id']))
            $this->tranzzoApi->setCustomerId($order_info['customer_id']);
        else
            $this->tranzzoApi->setCustomerId($order_info['email']);

        $this->tranzzoApi->setCustomerEmail($order_info['email']);

        $this->tranzzoApi->setCustomerFirstName($order_info['firstname']);

        $this->tranzzoApi->setCustomerLastName($order_info['lastname']);

        $this->tranzzoApi->setCustomerPhone($order_info['telephone']);

        $this->tranzzoApi->setProducts();

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
                );
            }

            $this->tranzzoApi->setProducts($items);
        }

        $response = $this->tranzzoApi->createPaymentHosted();

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

        $data = $_POST['data'];
        $signature = $_POST['signature'];
        if(empty($data) && empty($signature)) die('LOL! Bad Request!!!');

        $data_response = TranzzoApi::parseDataResponse($data);
        $order_id = (int)$data_response[TranzzoApi::P_RES_PROV_ORDER];
        if($this->tranzzoApi->validateSignature($data, $signature) && $order_id) {
            $this->load->language('extension/payment/tranzzo');
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            $amount_payment = TranzzoApi::amountToDouble($data_response[TranzzoApi::P_RES_AMOUNT]);
            $amount_order = TranzzoApi::amountToDouble($order_info['total'] * $order_info['currency_value']);
            if ($data_response[TranzzoApi::P_RES_RESP_CODE] == 1000 && ($amount_payment >= $amount_order)) {
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