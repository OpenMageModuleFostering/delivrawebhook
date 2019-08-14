<?php 

class Delivra_DelivraWebHook_Model_Cart extends Mage_Core_Model_Abstract {
    protected function _construct() {
        $this->_init('delivrawebhook/cart');
    }

    /**
     *   Add a new item to the cart
     */
    public function addToCart($quoteData) {
        Mage::log('Inserting cart value', null, 'delivra.log');

        $this->_getResource()->addToCart($this, $quoteData);
        return $this;
    }
}