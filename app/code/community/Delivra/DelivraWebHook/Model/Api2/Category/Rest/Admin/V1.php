<?php

class Delivra_DelivraWebHook_Model_Api2_Category_Rest_Admin_V1 extends Delivra_DelivraWebHook_Model_Api2_Category
{
    protected function _retrieveCollection() {
        $categories = Mage::getModel('catalog/category')
            ->getCollection()->addAttributeToSelect('name');
           
        $d = array(); 
        $i = 0;
        foreach ($categories as $category) {
            Mage::log($category->getName());
            $category->setCategoryName($category->getName());
            $d[$i] = array("entity_id" => $category->getId(), "category_name" => $category->getName());
            $i = $i + 1;
        }
          
        return $d;
    }
}