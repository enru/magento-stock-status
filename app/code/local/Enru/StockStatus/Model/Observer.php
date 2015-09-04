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
			// tally up qty of assoc products
			$qty=0;
                        $productIds = array();

                        $data = $product->getConfigurableProductsData();

                        if (is_array($data)) {
                                $productIds = array_keys($data);
                        }
                        else {
                                $childIds = Mage::getModel('catalog/product_type_configurable')->getChildrenIds($product->getId());
                                if(is_array($childIds) && is_array($childIds[0])) {
                                        $productIds = $childIds[0];
                                }
                        }

                        foreach($productIds as $prodId){
                                $sprod=Mage::getModel("catalog/product")->load($prodId);
                                $sqty = intval(Mage::getModel('cataloginventory/stock_item')->loadByProduct($sprod)->getQty());
                                $qty += $sqty;
                        }
		}

		// update the stock to in or out of stock depending on current qty
		$stockData['is_in_stock'] =  $qty ? 1 : 0;      
		Mage::log($product->getId() . " " . $stockData['is_in_stock']. "=" .$qty);
		$product->setStockData($stockData);
	}

}
