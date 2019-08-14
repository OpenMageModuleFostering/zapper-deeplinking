<?php
class Zapper_DeepLinking_Block_Form_DeepLinking extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate( 'zapper_deeplinking/form/deeplinking.phtml' );
    }

    public function getOrder() {
        $order = Mage::getModel('sales/order');
        $session = Mage::getSingleton('checkout/session');
        $order->loadByIncrementId($session->getLastRealOrderId());
        return $order;
    }
}
