<?php 

class Delivra_DelivraWebHook_Model_Resource_Cart extends Mage_Core_Model_Resource_Db_Abstract {
    protected function _construct() {
        $this->_init('delivrawebhook/cart', 'cart_id');
    }

    public function addToCart($cart, $quoteData) {
        $adapter = $this->_getWriteAdapter();

        $data = array();
        $data["store_id"] = $quoteData['store_id'];
        $data["quote_id"] = $quoteData['entity_id'];
        $adapter->insert($this->getMainTable(), $data);
    }
}