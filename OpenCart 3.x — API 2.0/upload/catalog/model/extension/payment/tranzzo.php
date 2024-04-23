<?php

class ModelExtensionPaymentTranzzo extends Model
{
    public function getMethod($address, $total)
    {

        if (!in_array($this->session->data['currency'], array('USD', 'EUR', 'UAH', 'RUB'))) {
            return false;
        }

        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('tranzzo_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

        if ($this->config->get('tranzzo_total') > 0 && $this->config->get('tranzzo_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('tranzzo_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $this->load->language('extension/payment/tranzzo');
            $method_data = array(
                'code' => 'tranzzo',
                'title' => $this->language->get('text_title'),
                'terms' => '',
                'sort_order' => $this->config->get('tranzzo_sort_order')
            );
        }

        return $method_data;
    }

    //new
    public function addPaymentData($order_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "order SET tracking = '" . $this->db->escape($data['order_id']) . "', tranzzo_payment = '" . $this->db->escape(json_encode($data)) . "' WHERE order_id = '" . (int)$order_id . "'");
    }

    public function getOrderId($tranzo_id)
    {
        $sql = "SELECT order_id FROM " . DB_PREFIX . "order WHERE tracking = '" . (int)$tranzo_id . "'";
        $query = $this->db->query($sql);
        return $query;
    }

    public function getStatusName($id, $lang)
    {
        $sql = "SELECT name FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$id . "' AND language_id = '" . (int)$lang . "'";
        $query = $this->db->query($sql);
        return $query;
    }

    public function getPaymentData($order_id)
    {
        $sql = "SELECT tranzzo_payment FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'";
        $query = $this->db->query($sql);

        $this->load->library('tranzzoApi');
        $this->tranzzoApi->writeLog(array('getPaymentData $sql', $sql));
        $this->tranzzoApi->writeLog(array('getPaymentData', $query));

        return $query->row['tranzzo_payment'];
    }
    //new
}