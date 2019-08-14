<?php 

class Delivra_DelivraWebHook_QuoteController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
    }
    
    public function restorecartAction() {
        $customerId = $this->getRequest()->getParam('id');
        $quoteId = (int) $this->getRequest()->getParam('quote');

        //clear existing cart
        $quote = Mage::getModel('checkout/cart')->getQuote();
        if ($quote->getId())
        {
            $quote->setIsActive(0)->save();
        }

        $quote = Mage::getModel('sales/quote')->load($quoteId);

        //logout current user if opening cart for someone else
        if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            $customerData = Mage::getSingleton('customer/session')->getCustomer();
            $sessionCustomerId = $customerData->getId();
            if($sessionCustomerId != $customerId){
                Mage::getSingleton('customer/session')->logout();
            }
        }
        
        //if loading cart that is already linked with order, just show empty cart
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $orderId = $quote->getReservedOrderId();
        if($orderId){
            $this->_redirect('checkout/cart'); 
            return;
        }

        //if cart is associated with a customer then make sure user is logged in
        if($customerId){
            if (!Mage::getSingleton('customer/session')->authenticate($this)) {
                $this->setFlag('', self::FLAG_NO_DISPATCH, true);
            }else{
                $userSession = Mage::getSingleton('customer/session');
       
                if ($quote->getId())
                {
                    $quote->setIsActive(1)->save();
                }

                $this->_redirect('checkout/cart'); 
            }
        }else{
            //guest
            if ($quote->getId())
            {
                $userSession = Mage::getSingleton('customer/session');       
                $quote->setIsActive(1);
                $quote->save();
                Mage::getSingleton('checkout/session')->setQuoteId($quoteId);
            }

            $this->_redirect('checkout/cart'); 
        }
    }
}