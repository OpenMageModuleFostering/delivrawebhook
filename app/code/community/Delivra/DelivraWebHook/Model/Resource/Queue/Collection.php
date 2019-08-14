<?php 

class Delivra_DelivraWebHook_Model_Resource_Queue_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract {
    protected function _construct() {
        $this->_map['fields']['webhookqueue_id'] = 'main_table.webhookqueue_id';
        $this->_init('delivrawebhook/queue');
    }
}