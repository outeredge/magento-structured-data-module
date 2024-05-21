<?php

namespace OuterEdge\StructuredData\Model\Api;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use OuterEdge\StructuredData\Api\OffersRepositoryInterface;
use OuterEdge\StructuredData\Model\Type\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use OuterEdge\StructuredData\Model\Cache\Type;

class OffersRepository implements OffersRepositoryInterface
{
    public function __construct(
        protected Product $structuredDataProduct,
        protected ProductRepositoryInterface $productRepository,
        protected CacheInterface $cache,
        protected SerializerInterface $serializer
    ) {
    }

    /**
     * @inheritdoc
     */
    public function offers($sku)
    {
        if (empty($sku)) {
            throw new Exception(new Phrase('Missing or empty sku value'));
        }

        $result = $this->getCache($sku);
        if (!$result) {
            $result = [$this->structuredDataProduct->getChildOffers($this->productRepository->get($sku))];
            $this->saveCache($sku, $result);
        }
        return $result;
    }

    protected function saveCache($sku, $data)
    {
        $cacheId  = Type::TYPE_IDENTIFIER . str_replace(' ', '_', $sku);
        $this->cache->save(
            $this->serializer->serialize($data),
            $cacheId,
            [Type::CACHE_TAG],
            86400
        );
    }

    protected function getCache($sku)
    {
        $cacheId  = Type::TYPE_IDENTIFIER . str_replace(' ', '_', $sku);

        if ($result = $this->cache->load($cacheId)) {
            return $this->serializer->unserialize($result);
        }
        return false;
    }
}
