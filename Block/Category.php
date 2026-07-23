<?php

namespace OuterEdge\StructuredData\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;

class Category extends \Magento\Framework\View\Element\Template
{
    /**
     * @var AbstractCollection
     */
    protected $_productCollection = null;

    public function __construct(
        Context $context,
        array $data = [])
    {
        parent::__construct($context, $data);
    }

    /**
     * Resolve the category product list block rendered on the page so
     * structured data reflects the actual visible list (sorting, layered
     * navigation, pagination, extensions, permissions).
     */
    private function getRenderedListBlock(): ?\Magento\Catalog\Block\Product\ListProduct
    {
        try {
            $layout = $this->getLayout();
            if (!$layout) {
                return null;
            }
            $block = $layout->getBlock('category.products.list');
            if ($block instanceof \Magento\Catalog\Block\Product\ListProduct) {
                return $block;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function getSchemaJson()
    {
        $collection = $this->getResolvedProductCollection();
        if (!$collection) {
            return '';
        }
        $schema = $this->getSchemaData($collection);
        $encoded = json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        return is_string($encoded) ? $encoded : '';
    }

    private function getResolvedProductCollection(): ?AbstractCollection
    {
        $block = $this->getRenderedListBlock();
        if ($block) {
            $collection = $block->getLoadedProductCollection();
            if ($collection instanceof AbstractCollection) {
                return $collection;
            }
        }

        return null;
    }

    public function getSchemaData(AbstractCollection $productCollection)
    {
        $this->_productCollection = $productCollection;

        $listData = [];

        $i = 1;
        foreach ($this->_productCollection as $product) {
            $listData[] = [
                "@type" => "ListItem",
                "position" => $i++,
                "url" => preg_replace('/[?#].*$/', '', (string) $product->getProductUrl()),
                "name" => (string) $product->getName()
            ];
        }

        return [
            "@type" => "ItemList",
            "itemListElement" => $listData
        ];
    }
}
