<?php

/**
 * Class ControllerExtensionPaymentTp
 */
class ControllerExtensionPaymentTp extends Controller
{
    /**
     * @var array
     */
    private $error = array();
    /**
     * @var array[]
     */
    private $fields = array(
        'payment_tp_pos_id' => array('required' => true, 'type' => 'text', 'display' => 'text'),
        'payment_tp_api_secret' => array('required' => true, 'type' => 'text', 'display' => 'password'),
        'payment_tp_api_key' => array('required' => true, 'type' => 'text', 'display' => 'password'),
        'payment_tp_endpoints_key' => array('required' => true, 'type' => 'text', 'display' => 'password'),
        'payment_tp_test_mode' => array('required' => true, 'type' => 'radio'),
        'payment_tp_type_payment' => array('required' => true, 'type' => 'radio'),
        'payment_tp_total' => array('required' => false, 'type' => 'text', 'display' => 'text'),
        'payment_tp_one_title' => array('required' => false, 'type' => 'title'),
        'payment_tp_custom_pending_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_order_status_complete_id' => array('required' => true, 'type' => 'select'),
        'payment_tp_order_status_failure_id' => array('required' => true, 'type' => 'select'),
        'payment_tp_order_status_listen' => array('required' => true, 'type' => 'select'),
        'payment_tp_two_title' => array('required' => false, 'type' => 'title'),
        'payment_tp_custom_auth_pending_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_order_status_auth_id' => array('required' => true, 'type' => 'select'),
        'payment_tp_custom_auth_failed_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_custom_auth_part_success_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_custom_auth_success_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_custom_auth_voided_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_custom_auth_refunded_status' => array('required' => true, 'type' => 'select'),
        'payment_tp_sort_order' => array('required' => false, 'type' => 'text', 'display' => 'text'),
        'payment_tp_geo_zone_id' => array('required' => false, 'type' => 'select'),
        'payment_tp_status' => array('required' => false, 'type' => 'select'),
    );
    /**
     * @var string
     */
    private $globalLabel = "TRANZZO";

    /**
     *
     */
    public function index()
    {
        $this->load->language('extension/payment/tp');
        $this->document->setTitle($this->globalLabel);
        $data = $this->language->all();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('payment_tp', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment/tp', 'user_token=' . $this->session->data['user_token']. '&type=payment', true));
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['error_order_status'] = false;

        foreach ($this->fields as $key => $value){
            $fieldKey = str_replace('payment_tp_', '',$key);
            $data['error_'.$fieldKey] = false;
        }

        if(!empty($this->error)){
            foreach ($this->error as $key => $value){
                $data['error_'.$key] = $value;
            }
        }

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->globalLabel,
            'href' => $this->url->link('extension/payment/tp', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/tp', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data = $this->prepareSettings($data);

        $data['user_token'] = $this->session->data['user_token'];

        $data['contact_text'] = $this->language->get('contact_text');
        $data['tp_url_doc'] = $this->language->get('tp_url_doc');
        $data['tp_text_doc'] = $this->language->get('tp_text_doc');

        $this->response->setOutput($this->load->view('extension/payment/tp', $data));

    }

    /**
     * @param $data
     * @return mixed
     */
    public function prepareSettings($data)
    {
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $currentLanguage = $this->language->get('code');

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        $this->fields['payment_tp_geo_zone_id']['options'] = $this->model_localisation_geo_zone->getGeoZones();
        $this->fields['payment_tp_geo_zone_id']['option_value'] = 'geo_zone_id';
        $this->fields['payment_tp_geo_zone_id']['option_name'] = 'name';
        $this->fields['payment_tp_geo_zone_id']['option_default_value'] = 0;
        $this->fields['payment_tp_geo_zone_id']['option_default_name'] = $this->language->get('text_all_zones');

        $this->fields['payment_tp_order_status_complete_id']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_order_status_complete_id']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_order_status_complete_id']['option_name'] = 'name';
        $this->fields['payment_tp_order_status_complete_id']['option_default_value'] = '';
        $this->fields['payment_tp_order_status_complete_id']['option_default_name'] = '---';
        //$this->fields['payment_tp_order_status_complete_id']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-purchase/overview/' :
            'https://docs.tranzzo.com/uk/docs/transactions-purchase/overview/';
        $this->fields['payment_tp_order_status_complete_id']['url'] = $url;

        $this->fields['payment_tp_order_status_auth_id']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_order_status_auth_id']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_order_status_auth_id']['option_name'] = 'name';
        $this->fields['payment_tp_order_status_auth_id']['option_default_value'] = '';
        $this->fields['payment_tp_order_status_auth_id']['option_default_name'] = '---';
        //$this->fields['payment_tp_order_status_auth_id']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-2-step/auth/' :
            'https://docs.tranzzo.com/uk/docs/transactions-2-step/auth/';
        $this->fields['payment_tp_order_status_auth_id']['url'] = $url;

        $this->fields['payment_tp_order_status_failure_id']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_order_status_failure_id']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_order_status_failure_id']['option_name'] = 'name';
        $this->fields['payment_tp_order_status_failure_id']['option_default_value'] = '';
        $this->fields['payment_tp_order_status_failure_id']['option_default_name'] = '---';
        //$this->fields['payment_tp_order_status_failure_id']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/status-codes/codes-by-stages/' :
            'https://docs.tranzzo.com/uk/docs/status-codes/codes-by-stages/';
        $this->fields['payment_tp_order_status_failure_id']['url'] = $url;

        $this->fields['payment_tp_order_status_listen']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_order_status_listen']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_order_status_listen']['option_name'] = 'name';
        $this->fields['payment_tp_order_status_listen']['option_default_value'] = '';
        $this->fields['payment_tp_order_status_listen']['option_default_name'] = '---';
        //$this->fields['payment_tp_order_status_listen']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-refund/overview/' :
            'https://docs.tranzzo.com/uk/docs/transactions-refund/overview/';
        $this->fields['payment_tp_order_status_listen']['url'] = $url;

        $this->fields['payment_tp_custom_pending_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_pending_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_pending_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_pending_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_pending_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_pending_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/status-codes/payment-cycle/' :
            'https://docs.tranzzo.com/uk/docs/status-codes/payment-cycle/';
        $this->fields['payment_tp_custom_pending_status']['url'] = $url;

        $this->fields['payment_tp_custom_auth_pending_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_auth_pending_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_auth_pending_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_auth_pending_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_auth_pending_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_auth_pending_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/status-codes/payment-cycle/' :
            'https://docs.tranzzo.com/uk/docs/status-codes/payment-cycle/';
        $this->fields['payment_tp_custom_auth_pending_status']['url'] = $url;

        $this->fields['payment_tp_custom_auth_failed_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_auth_failed_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_auth_failed_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_auth_failed_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_auth_failed_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_auth_failed_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/status-codes/codes-by-stages/' :
            'https://docs.tranzzo.com/uk/docs/status-codes/codes-by-stages/';
        $this->fields['payment_tp_custom_auth_failed_status']['url'] = $url;

        $this->fields['payment_tp_custom_auth_part_success_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_auth_part_success_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_auth_part_success_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_auth_part_success_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_auth_part_success_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_auth_part_success_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-2-step/capture/api/#capture-the-part-of-the-amount' :
            'https://docs.tranzzo.com/uk/docs/transactions-2-step/capture/api/#%D0%B7%D0%B0%D1%80%D0%B0%D1%85%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-%D1%87%D0%B0%D1%81%D1%82%D0%B8%D0%BD%D0%B8-%D1%81%D1%83%D0%BC%D0%B8-%D1%80%D0%B5%D0%B7%D0%B5%D1%80%D0%B2%D1%83';
        $this->fields['payment_tp_custom_auth_part_success_status']['url'] = $url;

        $this->fields['payment_tp_custom_auth_success_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_auth_success_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_auth_success_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_auth_success_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_auth_success_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_auth_success_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-2-step/capture/api/#capture-of-the-entire-amount' :
            'https://docs.tranzzo.com/uk/docs/transactions-2-step/capture/api/#%D0%B7%D0%B0%D1%80%D0%B0%D1%85%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-%D0%B2%D1%81%D1%96%D1%94%D1%97-%D1%81%D1%83%D0%BC%D0%B8-%D1%80%D0%B5%D0%B7%D0%B5%D1%80%D0%B2%D1%83';
        $this->fields['payment_tp_custom_auth_success_status']['url'] = $url;

        $this->fields['payment_tp_custom_auth_voided_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_auth_voided_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_auth_voided_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_auth_voided_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_auth_voided_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_auth_voided_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-2-step/void/' :
            'https://docs.tranzzo.com/uk/docs/transactions-2-step/void/';
        $this->fields['payment_tp_custom_auth_voided_status']['url'] = $url;

        $this->fields['payment_tp_custom_auth_refunded_status']['options'] =
            $this->model_localisation_order_status->getOrderStatuses();
        $this->fields['payment_tp_custom_auth_refunded_status']['option_value'] = 'order_status_id';
        $this->fields['payment_tp_custom_auth_refunded_status']['option_name'] = 'name';
        $this->fields['payment_tp_custom_auth_refunded_status']['option_default_value'] = '';
        $this->fields['payment_tp_custom_auth_refunded_status']['option_default_name'] = '---';
        //$this->fields['payment_tp_custom_auth_refunded_status']['description'] = $this->language->get('tp_statuses_description');
        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-refund/overview/' :
            'https://docs.tranzzo.com/uk/docs/transactions-refund/overview/';
        $this->fields['payment_tp_custom_auth_refunded_status']['url'] = $url;

        $this->fields['payment_tp_type_payment']['options'] = array(
            array(
                'id' => 1,
                'name' => $this->language->get('text_on'),
                'selector_id' => 'on'
            ),
            array(
                'id' => 0,
                'name' => $this->language->get('text_off'),
                'selector_id' => 'off'
            ),
        );
        $this->fields['payment_tp_type_payment']['option_value'] = 'id';
        $this->fields['payment_tp_type_payment']['option_name'] = 'name';

        $url = $currentLanguage == 'en' ?
            'https://docs.tranzzo.com/docs/transactions-2-step/auth/' :
            'https://docs.tranzzo.com/uk/docs/transactions-2-step/auth/';
        $this->fields['payment_tp_type_payment']['url'] = $url;

        $this->fields['payment_tp_test_mode']['options'] = array(
            array(
                'id' => 1,
                'name' => $this->language->get('text_on'),
                'selector_id' => 'test_mode_on'
            ),
            array(
                'id' => 0,
                'name' => $this->language->get('text_off'),
                'selector_id' => 'test_mode_off'
            ),
        );
        $this->fields['payment_tp_test_mode']['option_value'] = 'id';
        $this->fields['payment_tp_test_mode']['option_name'] = 'name';

        $this->fields['payment_tp_total']['description'] = $this->language->get('help_total');

        $this->fields['payment_tp_status']['options'] = array(
            array(
                'id' => 0,
                'name' => $this->language->get('text_disabled')
            ),
            array(
                'id' => 1,
                'name' => $this->language->get('text_enabled')
            ),
        );
        $this->fields['payment_tp_status']['option_value'] = 'id';
        $this->fields['payment_tp_status']['option_name'] = 'name';

        $this->fields['payment_tp_one_title']['description'] = $this->language->get('payment_tp_one_title');
        $this->fields['payment_tp_two_title']['description'] = $this->language->get('payment_tp_two_title');

        $requestPost = $this->request->post;

        foreach ($this->fields as $key => $options){

            $fieldKey = str_replace('payment_tp_', '',$key);

            if(key_exists('url', $this->fields[$key]) && !empty($this->fields[$key]['url'])) {
                $this->fields[$key]['label'] = sprintf($this->language->get('entry_' . $fieldKey), $this->fields[$key]['url']);
            }else{
                $this->fields[$key]['label'] = $this->language->get('entry_' . $fieldKey);
            }

            $this->fields[$key]['fieldKey'] = $fieldKey;

            if (isset($requestPost[$key])) {
                $data[$key] = $requestPost[$key];
                $this->fields[$key]['value'] = $requestPost[$key];
            } else {
                $data[$key] = $this->config->get($key);
                $this->fields[$key]['value'] = $this->config->get($key);
            }
        }

        $data['fields'] = $this->fields;

        return $data;
    }


    /**
     * @return bool
     */
    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/tp')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        foreach ($this->fields as $key => $options){
            if(($options['required'] && !isset($this->request->post[$key])) ||
                ($options['required'] && $this->request->post[$key] === '')){
                $fieldKey = str_replace('payment_tp_', '', $key);
                $this->error[$fieldKey] = $this->language->get('error_'.$fieldKey);
            }
        }

        return !$this->error;
    }

    /**
     * @param $route
     * @param $data
     * @param $output
     */
    public function order_list(&$route, &$data, &$output)
    {
        if(!empty($data['orders'])){
            $this->load->model('extension/payment/tp');
            foreach ($data['orders'] as $key => $order){
                $order_id = $data['orders'][$key]['order_id'];
                $order_info = $this->model_sale_order->getOrder($order_id);
                $transactionsData = $this->model_extension_payment_tp->searchTransactionsByOrderId($order_id);
                if(!empty($transactionsData)) {
                    $transactions = $this->load->view(
                        'extension/payment/tp_transactions',
                        array(
                            'transactions' => $transactionsData,
                            'currency_code' => !empty($this->currency->getSymbolRight($order_info['currency_code'])) ?
                                $this->currency->getSymbolRight($order_info['currency_code']) : $this->currency->getSymbolLeft($order_info['currency_code']),
                        )
                    );
                    $data['orders'][$key]['date_modified'] = $transactions;
                }

                $paymentData = $this->model_extension_payment_tp->getPaymentData($order_id);
                if($paymentData && !empty($paymentData)) {
                    $paymentData = json_decode($paymentData, true);
                }
                if(isset($paymentData['is_test'])){
                    $is_test = $paymentData['is_test'];
                    if($is_test){
                        $data['orders'][$key]['date_modified'] .= '<p><span style="display:block;width:100%;" class="label label-success">Test</span></p>';
                    }
                }
            }
        }
    }

    /**
     * @param $route
     * @param $data
     * @param $output
     */
    public function order_info(&$route, &$data, &$output)
    {
        $order_id = $this->request->get['order_id'];
        $this->load->model('extension/payment/tp');
        $order = $this->model_extension_payment_tp->getOrder($order_id);

        if ($order && $order['tp_payment']) {
            $metaData = $order['tp_payment'];
            if (!empty($metaData)) {
                $metaData = json_decode($metaData, true);

                if(isset($metaData['payment_id']) && !empty($metaData['payment_id'])) {
                    $this->load->model('sale/order');
                    $order_info = $this->model_sale_order->getOrder($order_id);
                    $params = $metaData;

                    /**Uncomment the code if you want to enable the refund function from the admin panel*/

                    /**
                    $tab['title'] = 'Refund';
                    $tab['code'] = 'tp_refund';
                    $total_refunded = $this->model_extension_payment_tp->getTotalRefundedAmount($order_id);
                    if($total_refunded >= $order_info['total']) {
                        $params['is_refunded'] = 1;
                        $params['amount_refunded'] = $this->currency->format($total_refunded, $order_info['currency_code'], $order_info['currency_value']);
                        $params['total_amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);
                    } else {
                        $params['is_refunded'] = 0;
                        $params['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value']);
                    }

                    $params['user_token'] = $this->session->data['user_token'];
                    $params['order_id'] = $order_id;

                    $content = $this->load->view('extension/payment/tp_refund', $params);

                    $tab['content'] = $content;
                    $data['tabs'][] = $tab;
                     */

                    //add transactions tab
                    $tabTransactions['title'] = 'Transactions';
                    $tabTransactions['code'] = 'tp_transactions';
                    $tabTransactions['content'] = $this->load->view(
                        'extension/payment/tp_transactions',
                        array(
                            'transactions' => $this->model_extension_payment_tp->searchTransactionsByOrderId($order_id),
                            'currency_code' => !empty($this->currency->getSymbolRight($order_info['currency_code'])) ?
                                $this->currency->getSymbolRight($order_info['currency_code']) : $this->currency->getSymbolLeft($order_info['currency_code']),
                        )
                    );
                    $data['tabs'][] = $tabTransactions;

                    $payment_method = '';
                    $fees = '';

                    if (!empty($order_id)) {
                        $payment_method = isset($metaData['method']) ? $metaData['method'] : '';
                        $fees = isset($metaData['fees']) ? $metaData['fees'] : '';
                        if (empty($payment_method) || empty($fees)) {

                            try {
                                $this->load->library('ServiceApi');
                                $paymentStatus = $this->ServiceApi->checkPaymentStatus(array('order_id' => $order_id));
                                if ($paymentStatus) {
                                    if (isset($paymentStatus['method'])) {
                                        $this->model_extension_payment_tp->updatePaymentData($order_id, 'payment_type', $paymentStatus['method']);
                                        $this->model_extension_payment_tp->updatePaymentData($order_id, 'fees', $paymentStatus['fee']);
                                    }
                                }
                            } catch (\Exception $e) {
                                $payment_method = $e->getMessage();
                            }
                        }

                        if (!empty($payment_method)) {
                            $data['totals'][] = array('title' => $this->globalLabel.' Payment Type', 'text' => ucwords(str_replace("_", " ", $payment_method)));
                            $data['totals'][] = array('title' => $this->globalLabel.' Fee', 'text' => $this->currency->format($fees, $order_info['currency_code'], $order_info['currency_value']));
                        }
                    }
                }
            }
        }
    }

    /**
     *
     */
    public function refund()
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            ini_set('serialize_precision', -1);
        }

        $this->load->language('extension/payment/tp');
        $this->load->library('ServiceApi');
        $this->load->model('extension/payment/tp');
        $this->ServiceApi->writeLog(array('$args', $this->request->post));

        $response = array();
        $status = 0;
        $message = '';

        try {
            if (isset($this->request->post['order_id'])) {
                $order_id = $this->request->post['order_id'];
            } else {
                $order_id = 0;
            }

            if (isset($this->request->post['tp_amount'])) {
                $tp_amount = $this->request->post['tp_amount'];
            } else {
                $tp_amount = 0;
            }

            if (isset($this->request->post['payment_id'])) {
                $transaction_id = $this->request->post['payment_id'];
            } else {
                $transaction_id = 0;
            }

            $this->load->model('sale/order');
            $order_info = $this->model_sale_order->getOrder($order_id);

            $order_total_paid = $order_info['total'];
            $amount = $tp_amount;
            $order_currency = $order_info['currency_code'];

            if ($amount <= 0) {
                throw new \Exception($this->language->get('error_refund_amount_0'));
            }

            if ($amount > $order_total_paid) {
                throw new \Exception($this->language->get('error_refund_total').' ('.$order_total_paid.')');
            }

            $metaData = (array)json_decode($this->model_extension_payment_tp->getPaymentData($order_id));
            $this->ServiceApi->writeLog(array('$payment_data', $metaData));

            if (!empty($metaData)) {

                if($metaData[ServiceApi::P_REQ_METHOD] == 'auth'){
                    $totalHold = $this->model_extension_payment_tp->getTotalHoldAmount($order_id);
                    if($amount < $totalHold) {

                        throw new \Exception(
                            $this->language->get('error_refund_amount_specify_total').
                            number_format( (float) $totalHold, 2, ',', '')
                        );
                    }
                }

                if($metaData[ServiceApi::P_REQ_METHOD] == 'capture'){
                    $captured = $this->model_extension_payment_tp->getTotalCapturedAmount($order_id);
                    if($amount > $captured) {

                        throw new \Exception(
                            $this->language->get('error_refund_amount_specify_total').
                            number_format( (float) $captured, 2, ',', '')
                        );
                    }
                }

                if ($this->config->get('payment_tp_test_mode') != '0') {
                    $order_currency = "XTS";
                    $amount = (int)$amount < (int)$order_total_paid ? 1 : 2;
                }

                $data = [
                    "order_currency" => $order_currency,
                    "refund_date" => date("Y-m-d H:i:s"),
                    "order_id" => strval($order_id),
                    "refund_amount" => strval($amount),
                ];

                switch ($metaData["method"]) {
                    case 'auth':
                        $response = $this->ServiceApi->createVoid($data);
                        break;
                    case 'capture':
                    case 'purchase':
                    case 'refund':
                    case 'void':
                        $response =  $this->ServiceApi->createRefund($data);
                        break;
                }

                if (!isset($response["status"]) || $response["status"] != "success") {
                    if(isset($response["status"]) && $response["status"] == 'failure'){

                        throw new \Exception(
                            $response["status_description"]
                        );
                    }

                    throw new \Exception(
                        $response["message"]
                    );
                }else{
                    $message = $this->language->get('text_refunded') . $amount . ', '.
                        $this->language->get('text_created_at') . $data['refund_date'];

                    $amount_payment = ServiceApi::amountToDouble($response[ServiceApi::P_REQ_AMOUNT]);

                    $payment_data = [
                        'method' => 'refund',
                        'order_id' => $response[ServiceApi::P_REQ_ORDER],
                        'refund_amount' => $amount_payment,
                        'payment_id' => $response[ServiceApi::P_RES_PAYMENT_ID],
                        'status' => $response['status']
                    ];

                    $this->model_extension_payment_tp->createTransaction($response[ServiceApi::P_REQ_METHOD], $amount_payment, $order_id);

                    $total_refunded = $this->model_extension_payment_tp->getTotalRefundedAmount($order_id);
                    if ($total_refunded >= $order_total_paid) {
                        $order = $this->model_extension_payment_tp->updatePaymentData($order_id, 'is_refunded', 1);

                        $this->model_checkout_order->addOrderHistory(
                            $order_id,
                            '13',
                            sprintf($this->language->get('text_pay_refund'), $this->globalLabel)."\n
                            {$this->language->get('text_payment_id')}: {$response[ServiceApi::P_RES_PAYMENT_ID]}\n
                            {$this->language->get('text_order')}: {$response[ServiceApi::P_REQ_ORDER]}",
                            true
                        );
                    }
                    $status = 1;

                    $this->model_extension_payment_tp->addPaymentData($order_id, $payment_data);
                }
            }else{

                throw new \Exception($this->language->get('error_refund_failed'));
            }
        } catch (\Exception $e) {
            $message = $this->language->get('error_refund_failed').': '.$e->getMessage();
        }

        $response['status'] = $status;
        $response['message'] = $message;

        echo json_encode($response);
        exit;
    }

    /**
     *
     */
    public function install()
    {
        $this->load->model('extension/payment/tp');
        $this->model_extension_payment_tp->install();
    }

    /**
     *
     */
    public function uninstall()
    {
        $this->load->model('extension/payment/tp');
        $this->model_extension_payment_tp->uninstall();
    }
}
