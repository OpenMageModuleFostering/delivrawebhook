<?php

class Delivra_DelivraWebHook_Model_Api2_Integration_Rest_Admin_V1 extends Delivra_DelivraWebHook_Model_Api2_Integration
{
    protected function _update($integrationData)
    {
        Mage::getConfig()->saveConfig('delivrawebhook/connection/url', $integrationData["url"], 'default', 0);
        Mage::helper('delivrawebhook/lists')->setConfigValue($integrationData["list_name"], $integrationData["api_key"]);
    }
    
    protected function _delete() {
        $list = $this->getRequest()->getParam('id');
        Mage::helper('delivrawebhook/lists')->removeList($list);
    }
}