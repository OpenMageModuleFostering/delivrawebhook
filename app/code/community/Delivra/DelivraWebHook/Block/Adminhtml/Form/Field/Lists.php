<?php

class Delivra_DelivraWebhook_Block_Adminhtml_Form_Field_Lists extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('listname', array(
            'label' => Mage::helper('adminhtml')->__('Account'),
            'style' => 'width:120px',
        ));
        $this->addColumn('apikey', array(
            'label' => Mage::helper('adminhtml')->__('API Key'),
            'style' => 'width:200px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add List');
        parent::__construct();
    }
}