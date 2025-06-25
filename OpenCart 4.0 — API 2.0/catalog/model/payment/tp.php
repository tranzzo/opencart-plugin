<?php
namespace Opencart\Catalog\Model\Extension\OcPaymentTP\Payment;

require_once ( DIR_EXTENSION.'oc_payment_tp/system/library/ServiceApi.php');

use Opencart\System\Library\Extension\OcPaymentTP\ServiceApi;

/**
 * Class ModelExtensionPaymentTp
 */
class TP extends \Opencart\System\Engine\Model
{
    /**
     * @var string
     */
    private $transactions_table_name;

    /**
     * @var string
     */
    private $globalLabel = "TRANZZO";

    private $supportCurrencyAPI = [
        "AED", "AFN", "ALL", "AMD", "ANG", "AOA", "ARS", "AUD", "AWG", "AZN",
        "BAM", "BBD", "BDT", "BGN", "BHD", "BIF", "BMD", "BND", "BOB", "BRL",
        "BSD", "BTN", "BWP", "BZD", "CAD", "CDF", "CHF", "CLP", "CNY",
        "COP", "CRC", "CUC", "CUP", "CVE", "CZK", "DJF", "DKK", "DOP", "DZD",
        "EGP", "ERN", "ETB", "EUR", "FJD", "FKP", "GBP", "GEL", "GHS", "GIP",
        "GMD", "GNF", "GTQ", "GYD", "HKD", "HNL", "HRK", "HTG", "HUF", "IDR",
        "ILS", "INR", "IQD", "ISK", "JMD", "JOD", "JPY", "KES", "KGS",
        "KHR", "KMF", "KRW", "KWD", "KYD", "KZT", "LAK", "LBP", "LKR",
        "LRD", "LSL", "LYD", "MAD", "MDL", "MGA", "MKD", "MMK", "MNT", "MOP",
        "MRO", "MUR", "MVR", "MWK", "MXN", "MYR", "MZN", "NAD", "NGN", "NIO",
        "NOK", "NPR", "NZD", "OMR", "PAB", "PEN", "PGK", "PHP", "PKR", "PLN",
        "PYG", "QAR", "RON", "RSD", "RWF", "SAR", "SBD", "SCR", "SDG",
        "SEK", "SGD", "SHP", "SLL", "SOS", "SRD", "SSP", "STD", "SVC", "SYP",
        "SZL", "THB", "TJS", "TMT", "TND", "TOP", "TRY", "TTD", "TWD", "TZS",
        "UAH", "UGX", "USD", "UYU", "UZS", "VEF", "VND", "VUV", "WST", "XAF",
        "XAG", "XAU", "XBA", "XBB", "XBC", "XBD", "XCD", "XDR", "XOF", "XPD",
        "XPF", "XPT", "XSU", "XTS", "XUA", "XXX", "YER", "ZAR", "ZMW", "ZWL",
    ];

    /**
     * ModelExtensionPaymentTp constructor.
     * @param $registry
     */
    public function __construct($registry) {
        parent::__construct($registry);
        $this->transactions_table_name = DB_PREFIX . 'tp_gateway_transactions';
    }

    /**
     * @param $address
     * @param int $total
     * @return array|bool
     */
    public function getMethods($address, $total = 0)
    {

        if (!in_array($this->session->data['currency'], $this->supportCurrencyAPI)) {
            return false;
        }
        if (!$this->config->get('config_checkout_payment_address')) {
            $status = true;
        }else{
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('tp_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
            if ($query->num_rows) {
                $status = true;
            } else {
                $status = false;
            }
        }

        if ($this->config->get('tp_total') > 0 && $this->config->get('tp_total') > $total) {
            $status = false;
        } elseif (!$this->config->get('tp_geo_zone_id')) {
            $status = true;
        } elseif ($query->num_rows) {
            $status = true;
        } else {
            $status = false;
        }

        $method_data = array();

        if ($status) {
            $this->load->language('extension/oc_payment_tp/payment/tp');
            $method_data = array(
                'code' => 'tp',
                'name' => sprintf($this->language->get('text_title'),$this->globalLabel),
                'option' => array(
                    'tp' => array(
                        "code" => "tp.tp",
                        'name' => sprintf($this->language->get('text_title'),$this->globalLabel),
                    ),
                ),
                'sort_order' => $this->config->get('tp_sort_order')
            );
        }

        return $method_data;
    }

    /**
     * @param $order_id
     * @param $data
     */
    public function addPaymentData($order_id, $data)
    {
        $this->db->query("UPDATE " . DB_PREFIX . "order SET tracking = '" . $this->db->escape($data['order_id']) . "', tp_payment = '" . $this->db->escape(json_encode($data)) . "' WHERE order_id = '" . (int)$order_id . "'");
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
     * @param $order_id
     * @return mixed
     */
    public function getPaymentData($order_id)
    {
        $sql = "SELECT tp_payment FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'";
        $query = $this->db->query($sql);

        //$this->load->library('ServiceApi');
        $this->ServiceApi = new ServiceApi($this->config);

        $this->ServiceApi->writeLog(array('getPaymentData $sql', $sql));
        $this->ServiceApi->writeLog(array('getPaymentData', $query));

        return $query->row['tp_payment'];
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

    /**
     * @param $order_id
     * @return |null
     */
    public function getFirstTransactionType($order_id) {
        $query = $this->db->query("SELECT type FROM " . $this->transactions_table_name . " WHERE order_id = '" . (int)$order_id . "' ORDER BY id ASC LIMIT 1");

        if ($query->num_rows) {
            return $query->row['type'];
        } else {
            return null;
        }
    }
}
