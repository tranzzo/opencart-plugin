<?php
class ControllerExtensionPaymentTranzzo extends Controller {
    private $error = array();

    public function index()
    {
        $this->load->language('extension/payment/tranzzo');
        $this->document->setTitle($this->language->get('heading_title'));
        $data = $this->language->all();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('tranzzo', $this->request->post);


            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/payment/tranzzo', 'token=' . $this->session->data['token'], true));
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

        $data['breadcrumbs'] = array();
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true)
        );
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/tranzzo', 'token=' . $this->session->data['token'], true)
        );

        $data['action'] = $this->url->link('extension/payment/tranzzo', 'token=' . $this->session->data['token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=payment', true);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $data = $this->prepareSettings($data);

        $data['token'] = $this->session->data['token'];

        $this->response->setOutput($this->load->view('extension/payment/tranzzo', $data));
    }

    public function prepareSettings($data)
    {
        $this->load->model('localisation/order_status');
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $this->load->model('localisation/geo_zone');
        $data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

        if (isset($this->request->post['tranzzo_geo_zone_id'])) {
            $data['tranzzo_geo_zone_id'] = $this->request->post['tranzzo_geo_zone_id'];
        } else {
            $data['tranzzo_geo_zone_id'] = $this->config->get('tranzzo_geo_zone_id');
        }

        if (isset($this->request->post['tranzzo_sort_order'])) {
            $data['tranzzo_sort_order'] = $this->request->post['tranzzo_sort_order'];
        } else {
            $data['tranzzo_sort_order'] = $this->config->get('tranzzo_sort_order');
        }

        if (isset($this->request->post['tranzzo_pos_id'])) {
            $data['tranzzo_pos_id'] = $this->request->post['tranzzo_pos_id'];
        } else {
            $data['tranzzo_pos_id'] = $this->config->get('tranzzo_pos_id');
        }
        if (isset($this->request->post['tranzzo_api_secret'])) {
            $data['tranzzo_api_secret'] = $this->request->post['tranzzo_api_secret'];
        } else {
            $data['tranzzo_api_secret'] = $this->config->get('tranzzo_api_secret');
        }
        if (isset($this->request->post['tranzzo_api_key'])) {
            $data['tranzzo_api_key'] = $this->request->post['tranzzo_api_key'];
        } else {
            $data['tranzzo_api_key'] = $this->config->get('tranzzo_api_key');
        }
        if (isset($this->request->post['tranzzo_endpoints_key'])) {
            $data['tranzzo_endpoints_key'] = $this->request->post['tranzzo_endpoints_key'];
        } else {
            $data['tranzzo_endpoints_key'] = $this->config->get('tranzzo_endpoints_key');
        }

        if (isset($this->request->post['tranzzo_status'])) {
            $data['tranzzo_status'] = $this->request->post['tranzzo_status'];
        } else {
            $data['tranzzo_status'] = $this->config->get('tranzzo_status');
        }
        if (isset($this->request->post['tranzzo_total'])) {
            $data['tranzzo_total'] = $this->request->post['tranzzo_total'];
        } else {
            $data['tranzzo_total'] = $this->config->get('tranzzo_total');
        }
        if (isset($this->request->post['tranzzo_order_status_complete_id'])) {
            $data['tranzzo_order_status_complete_id'] = $this->request->post['tranzzo_order_status_complete_id'];
        } else {
            $data['tranzzo_order_status_complete_id'] = $this->config->get('tranzzo_order_status_complete_id');
        }
        if (isset($this->request->post['tranzzo_order_status_failure_id'])) {
            $data['tranzzo_order_status_failure_id'] = $this->request->post['tranzzo_order_status_failure_id'];
        } else {
            $data['tranzzo_order_status_failure_id'] = $this->config->get('tranzzo_order_status_failure_id');
        }

        return $data;
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/payment/tranzzo')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        if (!$this->request->post['tranzzo_pos_id']) {
            $this->error['pos_id'] = $this->language->get('error_pos_id');
        }
        if (!$this->request->post['tranzzo_api_key']) {
            $this->error['api_key'] = $this->language->get('error_api_key');
        }
        if (!$this->request->post['tranzzo_api_secret']) {
            $this->error['api_secret'] = $this->language->get('error_api_secret');
        }
        if (!$this->request->post['tranzzo_endpoints_key']) {
            $this->error['endpoints_key'] = $this->language->get('error_endpoints_key');
        }

        if (!$this->request->post['tranzzo_order_status_complete_id']) {
            $this->error['order_status_complete_id'] = $this->language->get('error_order_status_complete_id');
        }
        if (!$this->request->post['tranzzo_order_status_failure_id']) {
            $this->error['order_status_failure_id'] = $this->language->get('error_order_status_failure_id');
        }

        $complete = (int)$this->request->post['tranzzo_order_status_complete_id'];
        $fail = (int)$this->request->post['tranzzo_order_status_failure_id'];
        if ($complete == $fail){
            $this->error['order_status'] = $this->language->get('error_order_status');
        }

        return !$this->error;
    }

    public function install()
    {
    }

    public function uninstall()
    {
    }
}