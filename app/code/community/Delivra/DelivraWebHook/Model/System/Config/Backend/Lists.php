<?php

class Delivra_DelivraWebhook_Model_System_Config_Backend_Lists extends Mage_Core_Model_Config_Data
{
    /**
     * Process data after load
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();
        $value = Mage::helper('delivrawebhook/lists')->makeArrayFieldValue($value);
        $this->setValue($value);
    }

    /**
     * Prepare data before save
     */
    protected function _beforeSave()
    {
        $value = $this->getValue();
        $value = Mage::helper('delivrawebhook/lists')->makeStorableArrayFieldValue($value);
        $this->setValue($value);
    }
}
