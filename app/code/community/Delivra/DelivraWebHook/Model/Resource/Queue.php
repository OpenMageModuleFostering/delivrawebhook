<?php 

class Delivra_DelivraWebHook_Model_Resource_Queue extends Mage_Core_Model_Resource_Db_Abstract {
    protected function _construct() {
        $this->_init('delivrawebhook/queue', 'webhookqueue_id');
    }

    /**
     *   Adds a new entry to the message queue to be processed in the next cycle
     */
    public function addMessageToQueue($queue, $orderData) {
        $adapter = $this->_getWriteAdapter();

        $data = array();
        $data["queue_status"] = 1;
        $data["retry_count"] = 0;
        $data["added_at"] = Mage::getSingleton('core/date')->gmtDate();
        $data["next_attempt_at"] = Mage::getSingleton('core/date')->gmtDate();
        $data["entity_id"] = $orderData->data["entity_id"];

        $data["serialized_data"] = json_encode($orderData);
        $data["entity_type"] = $orderData->entityType;
        $adapter->insert($this->getMainTable(), $data);
    }

    /**
     *   Gets all queue entries that are ready to be sent based on status and scheduled time
     */
    public function getPending($queue) {
        $adapter = $this->_getWriteAdapter();
        $adapter->beginTransaction();
        try {
            $readyStatuses = array(1, 3);
            $select = $adapter->select()->from($this->getMainTable())->where('queue_status IN (?)', $readyStatuses)->where('next_attempt_at <= ?', Mage::getSingleton('core/date')->gmtDate(Zend_Date::ISO_8601));
            $result = $adapter->fetchAssoc($select);
            $data['last_attempt_at'] = Mage::getSingleton('core/date')->gmtDate(Zend_Date::ISO_8601);
            $data['queue_status'] = 2;
            $adapter->update($this->getMainTable(), $data, array('queue_status IN (?)' => $readyStatuses));

            $adapter->commit();
        } catch (Exception $ex) {
            $adapter->rollBack();
        }

        if ($result) {
            return $result;
        }

        return array();
    }

    /**
     *   Removes an item from the queue
     */
    public function delete($queueId) {
        $adapter = $this->_getWriteAdapter();
        $adapter->delete($this->getMainTable(), array('webhookqueue_id = ?' => $queueId));
    }

    /**
     *   Sets a queue item to be retried in the future
     */
    public function reset($queueId) {
        $adapter = $this->_getWriteAdapter();
        $newDate = new Zend_Date(Mage::getSingleton('core/date')->gmtDate(Zend_Date::ISO_8601), Zend_Date::ISO_8601);
        $newDate = $newDate->addHour('1')->get(Zend_Date::ISO_8601);
        $adapter->update($this->getMainTable(), array('queue_status' => 3, 'retry_count' => new Zend_Db_Expr('retry_count + 1'), 'next_attempt_at' => $newDate), array('webhookqueue_id = ?' => $queueId));
    }
    
    public function cleanup() {
        $adapter = $this->_getWriteAdapter();
        
        $newDate = new Zend_Date(Mage::getSingleton('core/date')->gmtDate(Zend_Date::ISO_8601), Zend_Date::ISO_8601);
        $newDate = $newDate->addHour('-12')->get(Zend_Date::ISO_8601);
        
        $adapter->delete($this->getMainTable(), array('added_at <= ?' => $newDate, 'queue_status = ?' => 2));
    }
}