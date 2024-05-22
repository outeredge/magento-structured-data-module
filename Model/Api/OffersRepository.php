<?php

namespace OuterEdge\StructuredData\Model\Api;

use Magento\Framework\Phrase;
use Magento\Framework\Webapi\Exception;
use OuterEdge\StructuredData\Api\OffersRepositoryInterface;
use OuterEdge\StructuredData\Model\Type\Product;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use OuterEdge\StructuredData\Model\Cache\Type\StructuredDataCache;

class OffersRepository implements OffersRepositoryInterface
{
    public function __construct(
        protected Product $structuredDataProduct,
        protected ProductRepositoryInterface $productRepository,
        protected StructuredDataCache $cache,
        protected SerializerInterface $serializer
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

        $result = $this->getCache($productId);
        if (!$result) {
            $result = $this->structuredDataProduct->getChildOffers($this->productRepository->getById($productId));
            $this->saveCache($productId, $result);
        }
        return [$result];
    }

    protected function saveCache($productId, $data)
    {
        $cacheId  = StructuredDataCache::TYPE_IDENTIFIER .'_'. $productId;
        $this->cache->save(
            $this->serializer->serialize($data),
            $cacheId,
            [StructuredDataCache::CACHE_TAG],
        );
    }

    protected function getCache($productId)
    {
        $cacheId  = StructuredDataCache::TYPE_IDENTIFIER .'_'. $productId;

        if ($result = $this->cache->load($cacheId)) {
            return $this->serializer->unserialize($result);
        }
        return false;
    }
}
