<?php

class ControllerExtensionPaymentTranzzo extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/tranzzo');
        $this->document->setTitle($this->language->get('heading_title'));
        $data = $this->language->all();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('payment_tranzzo', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment/tranzzo', 'user_token=' . $this->session->data['user_token']. '&type=payment', true));
        }

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if (isset($this->error['pos_id'])) {
            $data['error_pos_id'] = $this->error['pos_id'];
        } else {
            $data['error_pos_id'] = '';
        }

        if (isset($this->error['api_key'])) {
            $data['error_api_key'] = $this->error['api_key'];
        } else {
            $data['error_api_key'] = '';
        }
        if (isset($this->error['api_secret'])) {
            $data['error_api_secret'] = $this->error['api_secret'];
        } else {
            $data['error_api_secret'] = '';
        }
        if (isset($this->error['endpoints_key'])) {
            $data['error_endpoints_key'] = $this->error['endpoints_key'];
        } else {
            $data['error_endpoints_key'] = '';
        }

        if (isset($this->error['order_status'])) {
            $data['error_order_status'] = $this->error['order_status'];
        } else {
            $data['error_order_status'] = '';
        }

        if (isset($this->error['order_status_complete_id'])) {
            $data['error_order_status_complete_id'] = $this->error['order_status_complete_id'];
        } else {
            $data['error_order_status_complete_id'] = '';
        }
        if (isset($this->error['order_status_failure_id'])) {
            $data['error_order_status_failure_id'] = $this->error['order_status_failure_id'];
        } else {
            $data['error_order_status_failure_id'] = '';
        }
        if (isset($this->error['order_status_listen'])) {
            $data['error_order_status_listen'] = $this->error['order_status_listen'];
        } else {
            $data['error_order_status_listen'] = '';
        }

        //new
        if (isset($this->error['order_status_auth_id'])) {
            $data['error_order_status_auth_id'] = $this->error['order_status_auth_id'];
        } else {
            $data['error_order_status_auth_id'] = '';
        }
        //new

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
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/tranzzo', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/tranzzo', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data = $this->prepareSettings($data);

        $data['user_token'] = $this->session->data['user_token'];

        $this->response->setOutput($this->load->view('extension/payment/tranzzo', $data));

    }

    public function prepareSettings($data)
    {
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        
        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['payment_tranzzo_geo_zone_id'])) {
            $data['payment_tranzzo_geo_zone_id'] = $this->request->post['payment_tranzzo_geo_zone_id'];
        } else {
            $data['payment_tranzzo_geo_zone_id'] = $this->config->get('payment_tranzzo_geo_zone_id');
        }

        if (isset($this->request->post['payment_tranzzo_sort_order'])) {
            $data['payment_tranzzo_sort_order'] = $this->request->post['payment_tranzzo_sort_order'];
        } else {
            $data['payment_tranzzo_sort_order'] = $this->config->get('payment_tranzzo_sort_order');
        }

        if (isset($this->request->post['payment_tranzzo_pos_id'])) {
            $data['payment_tranzzo_pos_id'] = $this->request->post['payment_tranzzo_pos_id'];
        } else {
            $data['payment_tranzzo_pos_id'] = $this->config->get('payment_tranzzo_pos_id');
        }
        if (isset($this->request->post['payment_tranzzo_api_secret'])) {
            $data['payment_tranzzo_api_secret'] = $this->request->post['payment_tranzzo_api_secret'];
        } else {
            $data['payment_tranzzo_api_secret'] = $this->config->get('payment_tranzzo_api_secret');
        }
        if (isset($this->request->post['payment_tranzzo_api_key'])) {
            $data['payment_tranzzo_api_key'] = $this->request->post['payment_tranzzo_api_key'];
        } else {
            $data['payment_tranzzo_api_key'] = $this->config->get('payment_tranzzo_api_key');
        }
        if (isset($this->request->post['payment_tranzzo_endpoints_key'])) {
            $data['payment_tranzzo_endpoints_key'] = $this->request->post['payment_tranzzo_endpoints_key'];
        } else {
            $data['payment_tranzzo_endpoints_key'] = $this->config->get('payment_tranzzo_endpoints_key');
        }

        if (isset($this->request->post['payment_tranzzo_status'])) {
            $data['payment_tranzzo_status'] = $this->request->post['payment_tranzzo_status'];
        } else {
            $data['payment_tranzzo_status'] = $this->config->get('payment_tranzzo_status');
        }
        if (isset($this->request->post['payment_tranzzo_total'])) {
            $data['payment_tranzzo_total'] = $this->request->post['payment_tranzzo_total'];
        } else {
            $data['payment_tranzzo_total'] = $this->config->get('payment_tranzzo_total');
        }
        if (isset($this->request->post['payment_tranzzo_order_status_complete_id'])) {
            $data['payment_tranzzo_order_status_complete_id'] = $this->request->post['payment_tranzzo_order_status_complete_id'];
        } else {
            $data['payment_tranzzo_order_status_complete_id'] = $this->config->get('payment_tranzzo_order_status_complete_id');
        }
        if (isset($this->request->post['payment_tranzzo_order_status_failure_id'])) {
            $data['payment_tranzzo_order_status_failure_id'] = $this->request->post['payment_tranzzo_order_status_failure_id'];
        } else {
            $data['payment_tranzzo_order_status_failure_id'] = $this->config->get('payment_tranzzo_order_status_failure_id');
        }
        if (isset($this->request->post['payment_tranzzo_order_status_listen'])) {
            $data['payment_tranzzo_order_status_listen'] = $this->request->post['payment_tranzzo_order_status_listen'];
        } else {
            $data['payment_tranzzo_order_status_listen'] = $this->config->get('payment_tranzzo_order_status_listen');
        }
        //new
        if (isset($this->request->post['payment_tranzzo_type_payment'])) {
            $data['payment_tranzzo_type_payment'] = $this->request->post['payment_tranzzo_type_payment'];
        } else {
            $data['payment_tranzzo_type_payment'] = $this->config->get('payment_tranzzo_type_payment');
        }
        if (isset($this->request->post['payment_tranzzo_order_status_auth_id'])) {
            $data['payment_tranzzo_order_status_auth_id'] = $this->request->post['payment_tranzzo_order_status_auth_id'];
        } else {
            $data['payment_tranzzo_order_status_auth_id'] = $this->config->get('payment_tranzzo_order_status_auth_id');
        }

        return $data;
    }


    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/tranzzo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['payment_tranzzo_pos_id']) {
            $this->error['pos_id'] = $this->language->get('error_pos_id');
        }
        if (!$this->request->post['payment_tranzzo_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['payment_tranzzo_api_secret']) {
            $this->error['api_secret'] = $this->language->get('error_api_secret');
        }
        if (!$this->request->post['payment_tranzzo_endpoints_key']) {
            $this->error['endpoints_key'] = $this->language->get('error_endpoints_key');
        }
        if (!$this->request->post['payment_tranzzo_order_status_complete_id']) {
            $this->error['order_status_complete_id'] = $this->language->get('error_order_status_complete_id');
        }
        if (!$this->request->post['payment_tranzzo_order_status_failure_id']) {
            $this->error['order_status_failure_id'] = $this->language->get('error_order_status_failure_id');
        }

        //new
        if ($this->request->post['payment_tranzzo_type_payment'] && !$this->request->post['payment_tranzzo_order_status_auth_id']) {
            $this->error['order_status_auth_id'] = $this->language->get('error_order_status_auth_id');
        }
        //new

        $complete = (int)$this->request->post['payment_tranzzo_order_status_complete_id'];
        //new
        $auth = (int)$this->request->post['payment_tranzzo_order_status_auth_id'];
        //new
        $fail = (int)$this->request->post['payment_tranzzo_order_status_failure_id'];
        if ($complete == $fail || $complete == $auth || $auth == $fail) {
            $this->error['order_status'] = $this->language->get('error_order_status');
        }

        return !$this->error;
    }

    public function install()
    {
	    $this->load->model('setting/event');

	    $this->model_setting_event->addEvent('tranzzo',
            'catalog/model/checkout/order/addOrderHistory/before',
            'extension/payment/tranzzo/tranzzoRefund'
        );

        //new
        $this->load->model('extension/payment/tranzzo');
        $this->model_extension_payment_tranzzo->install();
        //new
    }

    public function uninstall()
    {
	    $this->load->model('setting/event');
	    $this->model_setting_event->deleteEvent('tranzzo');

        //new
        $this->load->model('extension/payment/tranzzo');
        $this->model_extension_payment_tranzzo->uninstall();
        //new
    }
}
