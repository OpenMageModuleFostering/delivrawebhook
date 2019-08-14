<?php 

class Delivra_DelivraWebHook_Model_Queue extends Mage_Core_Model_Abstract {
    protected function _construct() {
        $this->_init('delivrawebhook/queue');
    }

    /**
     *   Add a new item to the queue
     */
    public function enqueue($orderData) {
        Mage::log('Inserting queue value', null, 'delivra.log');

        $this->_getResource()->addMessageToQueue($this, $orderData);
        return $this;
    }

    /**
     *   Gets the pending items and places them in state of processing
     */
    public function dequeueStart() {
        return $this->_getResource()->getPending($this);
    }

    /**
     *   Called when a queue item has been successfully processed
     */
    public function dequeueComplete($queueId) {
        Mage::log('removing from queue: '.$queueId, null, 'delivra.log');
        $this->_getResource()->delete($queueId);
    }

    /**
     *   Called when a queue item encountered an error while processing
     */
    public function dequeueError($queueId, $retry = true) {
        Mage::log('updating queue item for error: '.$queueId, null, 'delivra.log');
        if ($retry) {
            $this->_getResource()->reset($queueId);
        } else {
            $this->_getResource()->delete($queueId);
        }
    }
    
    public function cleanup() {
        $this->_getResource()->cleanup($this);
    }
}