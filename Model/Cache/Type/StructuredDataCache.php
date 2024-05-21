<?php

namespace OuterEdge\StructuredData\Model\Cache\Type;

use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\Type\FrontendPool;

class StructuredDataCache extends TagScope
{
    const TYPE_IDENTIFIER = 'outeredge_structureddata_cache_';

    const CACHE_TAG = 'OUTEREDGE_STRUCTUREDDATA_CACHE';

    public function __construct(
        FrontendPool $cacheFrontendPool
    ){
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}