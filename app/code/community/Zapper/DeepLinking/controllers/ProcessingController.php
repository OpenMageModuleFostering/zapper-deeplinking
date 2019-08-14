<?php
class Zapper_DeepLinking_ProcessingController extends Mage_Core_Controller_Front_Action
{
    public function testAction() {
        $deeplinking = Mage::getModel('deeplinking/deeplinking');

        $order = $deeplinking->getOrder();

        $fields['merchantId'] = $deeplinking->getMerchantID();
        $fields['siteId'] = $deeplinking->getSiteID();
        $fields['amount'] = number_format($order->getGrandTotal(),2,'','');
        $fields['amountType'] = 11; // read only
        $fields['tip'] = 0; // not built into STQ yet
        $fields['merchantReference'] = $order->getRealOrderId();
        $fields['shortMerchantName'] = Mage::app()->getStore()->getName();
        $fields['currencyISOCode'] = Mage::app()->getStore()->getBaseCurrencyCode(); // ZAR

        $urlParams = urlencode("http://2.zap.pe?t=6&");
        $urlParams .= "i=" . urlencode($fields['merchantId'] .":" . $fields['siteId'] . ":7[34|" . $fields['amount'] . "|" . $fields['amountType'] . ",66|" . $fields['merchantReference'] . "|10,60|1:10[38|" . $fields['shortMerchantName'] . ",39|" . $fields['currencyISOCode']);
        $urlParams .= "&appName=" . urlencode($fields['appName']);
        $urlParams .= "&successCallbackURL=" . urlencode(Mage::getBaseUrl() . "zapperdeeplinking/processing/success/");
        $urlParams .= "&failureCallbackURL=" . urlencode(Mage::getBaseUrl() . "zapperdeeplinking/processing/failure/");

        echo $urlParams;
    }

    public function setZapperIdAction() {
      $zapperId = $this->getRequest()->getParam('id');
      Mage::getSingleton('core/session')->setZapperId($zapperId);
      echo Mage::getSingleton('core/session')->getZapperId();
    }

    public function redirectAction()
    {
      $deeplinking = Mage::getModel('deeplinking/deepLinking');
      $order = $deeplinking->getOrder();

      //Get Zapper payment Id
      $zapperPaymentId = Mage::getSingleton('core/session')->getZapperId();
      Mage::getSingleton('core/session')->setZapperId(0);
      if ($zapperPaymentId == "") {
        $this->_redirectUrl(Mage::getBaseUrl() . "zapperdeeplinking/processing/failure/");
      }

      $merchantId = $deeplinking->getMerchantID();
      $siteId = $deeplinking->getSiteID();
      $posKey = $deeplinking->getPosKey();
      $posSecret = $deeplinking->getPosSecret();
      $signature = $this->createSignature($posKey, $posSecret);

      $url = $deeplinking->getZapperUrl() . '/merchants/' . $merchantId . '/sites/' . $siteId . '/payments/' . $zapperPaymentId;

      $opts = array(
        'http'=>array(
          'method'=>"GET",
          'header'=>"SiteId: " . $siteId ."\r\n" .
            "PosKey: " . $posKey . "\r\n" .
            "Signature: " . $signature . "\r\n"
        )
      );

      $context = stream_context_create($opts);

      // Open the file using the HTTP headers set above
      $file = file_get_contents($url, false, $context);

      $zapperOrder = json_decode($file);

      if ($zapperOrder->data == null || count($zapperOrder->data) == 0) {
        // echo "failure";
        $this->_redirectUrl(Mage::getBaseUrl() . "zapperdeeplinking/processing/failure/");
      }

      $zapperPayment = $zapperOrder->data[0];
      $lastOrder = $deeplinking->getOrder();

      if ($zapperPayment->ReceiptStatus == 2) {
        $this->_redirectUrl(Mage::getBaseUrl() . "zapperdeeplinking/processing/success");
      } else {
        $this->_redirectUrl(Mage::getBaseUrl() . "zapperdeeplinking/processing/failure");
      }
    }

    public function successAction()
    {
        $deeplinking = Mage::getModel('deeplinking/deepLinking');
        $order = $deeplinking->getOrder();

        $invoice = $order->prepareInvoice();
        $invoice->register()->capture();
        $order->addRelatedObject($invoice);
        $email = ($deeplinking->getOrderSuccessfulEmail() ? true : false);
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING,'The user completed payment with the Zapper App',$email);
        if ($email)
            $deeplinking->sendPaymentSuccessfulEmail($order);

        $order->save();

        $this->_redirect('checkout/onepage/success', array('_secure'=> false));
    }

    public function failureAction()
    {
        $deeplinking = Mage::getModel('deeplinking/deepLinking');
        $order = $deeplinking->getOrder();
        $email = ($deeplinking->getOrderFailedEmail() ? true : false);
        $order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED,'The user failed to make payment with the Zapper App.',$email);
        if ($email)
            $deeplinking->sendPaymentFailedEmail($order);

        $order->save();
        $this->_redirect('checkout/onepage/failure', array('_secure'=> false));
    }

    private function createSignature($posKey, $posSecret) {
      return hash('sha256', strtoupper($posSecret . '&' . $posKey));
    }
}
