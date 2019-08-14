<?php
class Zapper_DeepLinking_Model_DeepLinking extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'zapperdeeplinking';
    protected $_formBlockType = 'deeplinking/form_deepLinking';
    protected $_canCapture = true;

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('zapperdeeplinking/processing/redirect', array('_secure' => false));
    }

    public function getMerchantID() {
        return Mage::getStoreConfig('payment/zapperdeeplinking/merchant_id');
    }

    public function getSiteID() {
        return Mage::getStoreConfig('payment/zapperdeeplinking/site_id');
    }

    public function getPosKey() {
        return Mage::getStoreConfig('payment/zapperdeeplinking/pos_key');
    }

    public function getPosSecret() {
        return Mage::getStoreConfig('payment/zapperdeeplinking/pos_secret');
    }

    public function getAppName() {
        return Mage::getStoreConfig('payment/zapperdeeplinking/app_name');
    }

    public function getOrderStatus() {
        return $this->getConfigData('order_status');
    }

    public function getOrderPlacedEmail() {
        return $this->getConfigData('order_placed_email');
    }

    public function getOrderSuccessfulEmail() {
        return $this->getConfigData('order_successful_email');
    }

    public function getOrderFailedEmail() {
        return $this->getConfigData('order_failed_email');
    }

    public function getZapperUrl() {
      // return "https://zapapi.zapzap.mobi/ecommerce/api/v2/";
      return "https://zapqa.zapzapadmin.com/qa-zapperpointofsale/api/v2/";
    }

    public function getOrder() {
        $order = Mage::getModel('sales/order');
        $session = Mage::getSingleton('checkout/session');
        $order->loadByIncrementId($session->getLastRealOrderId());
        return $order;
    }
}
