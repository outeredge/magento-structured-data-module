<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class Category extends ListProduct
{
    /**
     * @var AbstractCollection
     */
    protected $_productCollection = null;

    public function getSchemaJson()
    {
        return json_encode($this->getSchemaData($this->getLoadedProductCollection()), JSON_UNESCAPED_SLASHES);
    }

    public function getSchemaData(AbstractCollection $productCollection)
    {
        if (!$this->_productCollection) {
            $this->_productCollection = $productCollection;
        }

        $listData = [];

        $i = 1;
        foreach ($this->_productCollection as $product) {
            $listData[] = [
                "@context" => "https://schema.org/",
                "@type" => "ListItem",
                "position" => $i++,
                "url" => $this->getProductUrl($product)
            ];
        }

        $data = [
            "@type" => "ItemList",
            "itemListElement" => $listData
        ];

        return $data;
    }
}
