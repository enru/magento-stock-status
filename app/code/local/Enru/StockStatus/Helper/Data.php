<?php

class Enru_StockStatus_Helper_Data  extends Mage_Core_Helper_Abstract {

    function getConfigurableProductQty($product) {

        $qty=0;

        // if it's a configurable product
        if($product->getData('type_id') == 'configurable' && $product->getId()) {

            // tally up qty of assoc products
            $productIds = array();

            $data = $product->getConfigurableProductsData();

            if (is_array($data)) {
                $productIds = array_keys($data);
            }
            else {
                $childIds = Mage::getModel('catalog/product_type_configurable')
                    ->getChildrenIds($product->getId());

                if(is_array($childIds) && is_array($childIds[0])) {
                    $productIds = $childIds[0];
                }
            }

            foreach($productIds as $prodId){
                $sProd=Mage::getModel("catalog/product")->load($prodId);
                $sQty = intval(Mage::getModel('cataloginventory/stock_item')
                    ->loadByProduct($sProd)->getQty());
                $qty += $sQty;
            }
        }

        return $qty;
    }
}

