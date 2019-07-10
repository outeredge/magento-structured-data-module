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
     * Need to implement review rating for the product page in here.
     * @return string
     */
    public function getReviewsRating()
    {
        return '';
    }

    /**
     * Need to implement review count for the product page in here.
     * @return string
     */
    public function getReviewsCount()
    {
        return '';
    }

    /**
     * Need to implement reviews for the product page in here.
     * @return array
     */
    public function getReviews()
    {
        return array();
    }
}
