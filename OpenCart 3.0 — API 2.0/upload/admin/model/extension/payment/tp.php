<?php

use Cardinity\Client;
use Cardinity\Method\Payment;
use Cardinity\Method\Refund;

/**
 * Class ModelExtensionPaymentTp
 */
class ModelExtensionPaymentTp extends Model
{
    /**
     * @var string
     */
    private $transactions_table_name;

    /**
     * ModelExtensionPaymentTp constructor.
     * @param $registry
     */
    public function __construct($registry) {
        parent::__construct($registry);
        $this->transactions_table_name = DB_PREFIX . 'tp_gateway_transactions';
    }

    /**
     * Run a function during installation
     */
    public function install()
    {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` ADD tp_payment TEXT");
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "tp_gateway_transactions` (
                id BIGINT(20) NOT NULL AUTO_INCREMENT,
                type ENUM('purchase','auth','capture','void','refund') NOT NULL,
                amount DECIMAL(26,8) DEFAULT NULL,
                date DATETIME DEFAULT CURRENT_TIMESTAMP,
                order_id BIGINT(20) DEFAULT NULL,
                PRIMARY KEY (id),
                KEY order_id_key (order_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('payment_tp', 'admin/view/sale/order_info/before', 'extension/payment/tp/order_info');
        $this->model_setting_event->addEvent('payment_tp_order_list', 'admin/view/sale/order_list/before', 'extension/payment/tp/order_list');
    }

    /**
     * Run a function during uninstall
     */
    public function uninstall()
    {
        $this->db->query("ALTER TABLE `" . DB_PREFIX . "order` DROP tp_payment");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "tp_gateway_transactions`;");
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('payment_tp');
        $this->model_setting_event->deleteEventByCode('payment_tp_order_list');
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function getOrder($order_id) {
        $qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `order_id` = '" . (int)$order_id . "' LIMIT 1");

        if ($qry->num_rows) {
            $order = $qry->row;
            return $order;
        } else {
            return false;
        }
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function getPaymentData($order_id)
    {
        $qry = $this->db->query('select tp_payment FROM ' . DB_PREFIX.'order WHERE order_id='.(int)($order_id));
        if ($qry->num_rows) {
            $row = $qry->row;
            return $row['tp_payment'];
        } else {
            return false;
        }
    }

    /**
     * @param $order_id
     * @param $response
     */
    public function addPaymentData($order_id, $response)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET `order_id` = '" . (int)$order_id . "',  `tp_payment` = '" . $this->db->escape($response) . "'");
    }

    /**
     * @param $order_id
     * @param $param
     * @param $value
     */
    public function updatePaymentData($order_id, $param, $value)
    {
        $metaData = $this->getPaymentData($order_id);
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
            $metaData[$param] = $value;
            $paymentData = json_encode($metaData);
            $this->db->query("UPDATE " . DB_PREFIX . "order SET tp_payment = '" . $this->db->escape($paymentData) . "' WHERE order_id = '" . (int)$order_id . "'");
        }
    }

    /**
     * @param $order_id
     * @param $param
     */
    public function deletePaymentData($order_id, $param)
    {
        $metaData = $this->getPaymentData($order_id);
        if (!empty($metaData)) {
            $metaData = json_decode($metaData, true);
            if (isset($metaData[$param])) {
                unset($metaData[$param]);
            }
            $paymentData = json_encode($metaData);

            $this->db->query("UPDATE " . DB_PREFIX . "order SET tp_payment = '" . $this->db->escape($paymentData) . "' WHERE order_id = '" . (int)$order_id . "'");
        }
    }

    /**
     * @param $tranzo_id
     * @return mixed
     */
    public function getOrderId($tranzo_id)
    {
        $sql = "SELECT order_id FROM " . DB_PREFIX . "order WHERE tracking = '" . (int)$tranzo_id . "'";
        $query = $this->db->query($sql);
        return $query;
    }

    /**
     * @param $id
     * @param $lang
     * @return mixed
     */
    public function getStatusName($id, $lang)
    {
        $sql = "SELECT name FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$id . "' AND language_id = '" . (int)$lang . "'";
        $query = $this->db->query($sql);
        return $query;
    }

    /**
     * @param $type
     * @param $amount
     * @param $order_id
     * @return mixed
     */
    public function createTransaction($type, $amount, $order_id) {
        $this->db->query("INSERT INTO " . $this->transactions_table_name . " SET type = '" . $this->db->escape($type) . "', amount = '" . (float)$amount . "', order_id = '" . (int)$order_id . "'");

        return $this->db->getLastId();
    }

    /**
     * @param $id
     * @param $type
     * @param $amount
     * @param $order_id
     */
    public function updateTransaction($id, $type, $amount, $order_id) {
        $this->db->query("UPDATE " . $this->transactions_table_name . " SET type = '" . $this->db->escape($type) . "', amount = '" . (float)$amount . "', order_id = '" . (int)$order_id . "' WHERE id = '" . (int)$id . "'");
    }

    /**
     * @param $id
     */
    public function deleteTransaction($id) {
        $this->db->query("DELETE FROM " . $this->transactions_table_name . " WHERE id = '" . (int)$id . "'");
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getTransaction($id) {
        $query = $this->db->query("SELECT * FROM " . $this->transactions_table_name . " WHERE id = '" . (int)$id . "'");

        return $query->row;
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public function searchTransactionsByOrderId($order_id) {
        $query = $this->db->query("SELECT * FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' ORDER BY date DESC");

        return $query->rows;
    }

    /**
     * @param $order_id
     * @return float
     */
    public function getTotalCapturedAmount($order_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' AND type = 'capture'");

        return (float)$query->row['total'];
    }

    /**
     * @param $order_id
     * @return float
     */
    public function getTotalRefundedAmount($order_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' AND type = 'refund'");

        return (float)$query->row['total'];
    }

    /**
     * @param $order_id
     * @return float
     */
    public function getTotalVoidedAmount($order_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' AND type = 'void'");

        return (float)$query->row['total'];
    }

    /**
     * @param $order_id
     * @return float
     */
    public function getTotalPurchasedAmount($order_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' AND type = 'purchase'");

        return (float)$query->row['total'];
    }

    /**
     * @param $order_id
     * @return float
     */
    public function getTotalHoldAmount($order_id) {
        $query = $this->db->query("SELECT SUM(amount) AS total FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' AND type = 'auth'");

        return (float)$query->row['total'];
    }
}
