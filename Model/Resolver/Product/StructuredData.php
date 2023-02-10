<?php

namespace OuterEdge\StructuredData\Model\Resolver\Product;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use OuterEdge\StructuredData\Model\Type\Product as ProductData;

class StructuredData implements ResolverInterface
{

    /**
     * @var ProductData
     */
    protected $productData;

    /**
     * @param ProductData $productData
     */
    public function __construct(
        ProductData $productData
    ) {
        $this->productData = $productData;
    }

    /**
     * @inheritdoc
     *
     * Get Schema.org Structured Data for products
     *
     * @param \Magento\Framework\GraphQl\Config\Element\Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @throws \Exception
     * @return array
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var ProductInterface $product */
        $product = $value['model'];


        return json_encode($this->productData->getSchemaData($product), JSON_UNESCAPED_SLASHES);
    }
}
