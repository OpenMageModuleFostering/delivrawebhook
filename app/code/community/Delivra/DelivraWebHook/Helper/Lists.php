<?php 
class Delivra_DelivraWebHook_Helper_Lists {
    public function setConfigValue($listname, $apikey, $store = null)
    {
        $value = Mage::getStoreConfig("delivrawebhook/connection/lists", $store);
        $value = $this->_unserializeValue($value);
        
        $value[$listname] = $apikey;
        $value = $this->_serializeValue($value);
        Mage::getConfig()->saveConfig('delivrawebhook/connection/lists', $value, 'default', 0);
    }

    public function getConfigValues($store = null)
    {
        $value = Mage::getStoreConfig("delivrawebhook/connection/lists", $store);
        $value = $this->_unserializeValue($value);
        return $value;
    }
    
    public function removeList($listName, $store = null) {
        $value = Mage::getStoreConfig("delivrawebhook/connection/lists", $store);
        $value = $this->_unserializeValue($value);
        
        unset($value[$listname]);
        $value = $this->_serializeValue($value);
        Mage::getConfig()->saveConfig('delivrawebhook/connection/lists', $value, 'default', 0);
    }

    protected function _serializeValue($value)
    {
        return json_encode($value);
    }

    protected function _unserializeValue($value)
    {
        return json_decode($value, true);
    }

    public function makeArrayFieldValue($value)
    {
        $value = $this->_unserializeValue($value);
        if(is_null($value)) {
            $value = '';
        }
        
        if (is_array($value)){
            return $this->_encodeArrayFieldValue($value);
        } else {
            return null;
        }
    }

    public function makeStorableArrayFieldValue($value)
    {
        $value = $this->_decodeArrayFieldValue($value);
        $value = $this->_serializeValue($value);
        return $value;
    }

    protected function _decodeArrayFieldValue(array $value)
    {
        if (!is_array($value)){
            return $value;
        }
        
        $result = array();
        unset($value['__empty']);
        foreach ($value as $_id => $row) {
            if (!is_array($row) || !array_key_exists('listname', $row)|| !array_key_exists('apikey', $row)) {
                continue;
            }
            $listname = $row['listname'];
            $apikey = $row['apikey'];
            $result[$listname] = $apikey;
        }
        return $result;
    }
    
    protected function _encodeArrayFieldValue(array $value)
    {
        if (!is_array($value)){
            return $value;
        }
        
        $result = array();
        foreach ($value as $listname => $apikey) {
            $_id = Mage::helper('core')->uniqHash('_');
            $result[$_id] = array(
                'listname' => $listname,
                'apikey' => $apikey
            );
        }
        return $result;
    }

}