<?php

class Enru_StockStatus_Model_Observer {

    function setStockStatus($observer) {

        $product = $observer->getProduct();

        $stockData = $product->getStockData();

        if(!is_array($stockData) || !isset($stockData['qty'])) {
            $stockData = array(
                'qty' => intval(Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty()),
            );
        }

        $qty = $stockData['qty'];

        // if it's a configurable product
        if($product->getData('type_id') == 'configurable' && $product->getId()) {
            $qty = Mage::helper('enrustockstatus')->getConfigurableProductQty($product);
        }

        // update the stock to in or out of stock depending on current qty
        $stockData['is_in_stock'] =  ($qty > 0) ? 1 : 0;      
        Mage::log("stock status of Product ID " . $product->getId() . "=" . $stockData['is_in_stock']. ", qty=" .$qty);
        $product->setStockData($stockData);
    }

    function setParentStockStatus($observer) {

        $product = $observer->getProduct();

        // update parents
        if($product->getData('type_id') == 'simple' && $product->getId()) {

            $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')
              ->getParentIdsByChild($product->getId());

            if(is_array($parentIds) && count($parentIds) > 0) {

                foreach($parentIds as $parentId) {
                    Mage::log("updating parent product stock status. parent product ID = ".$parentId);
                    $parent = Mage::getModel('catalog/product')->load($parentId);
                    $qty = Mage::helper('enrustockstatus')->getConfigurableProductQty($parent);
                    $stockData = array(
                        'is_in_stock' =>  ($qty > 0) ? 1 : 0,      
                        'qty' =>$qty,
                    );
                    Mage::log("stock status of Parent ID " . $parent->getId() . "=" . $stockData['is_in_stock']. ", qty=" .$qty);
                    $parent->setStockData($stockData);
                    $parent->save();
                }
            }
        }

    }

    function massStockChange($observer) {
        Mage::log('mass stock change');

        $productIds = $observer->getEvent()->getProducts();
        Mage::log(var_export($productIds, true));
        foreach ($productIds as $id) {
            $product = Mage::getModel('catalog/product')->load($id);
            $obj = new Varien_Object();
            $obj->setProduct($product);
            $this->setStockStatus($obj);
            $this->setParentStockStatus($obj);
        }
        return $this;
    }

}
