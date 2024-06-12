<?php

namespace OuterEdge\StructuredData\Model\Api;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use OuterEdge\StructuredData\Api\OffersRepositoryInterface;
use OuterEdge\StructuredData\Model\Type\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;

class OffersRepository implements OffersRepositoryInterface
{
    public function __construct(
        protected Product $structuredDataProduct,
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * @inheritdoc
     */
    public function offers($productId)
    {
        if (empty($productId)) {
            throw new Exception(new Phrase('Missing or empty product id'));
        }

        return [$this->structuredDataProduct->getChildOffers($this->productRepository->getById($productId))];
    }
}
