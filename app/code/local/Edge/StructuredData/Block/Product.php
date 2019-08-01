<?php

class Edge_StructuredData_Block_Product extends Mage_Core_Block_Template
{
    /**
     * @return array
     */
    public function getChildren()
    {
        $product = $this->getProduct();

        $childProducts = [];
        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE) {
            $childProducts = Mage::getModel('catalog/product_type_configurable')
            ->getUsedProducts(null, $product);
        }

        return $childProducts;
    }

    /**
     * @return Mage_Catalog_Model_Product|mixed
     */
    public function getProduct()
    {
        return Mage::registry('current_product');
    }

    /**
     * @return int
     */
    public function getReviewsRating()
    {   
        return $this->getReviewData()->getRatingSummary() / 20;
    }

    /**
     * @return int
     */
    public function getReviewsCount()
    {
        return $this->getReviewData()->getReviewsCount();
    }

    private function getReviewData()
    {
        $storeId = Mage::app()->getStore()->getId();
        $product = $this->getProduct();
        return Mage::getModel('review/review_summary')->setStoreId($storeId)->load($product->getId());
    }
}
