<?php

namespace OuterEdge\StructuredData\Model\Cache;

use Magento\Framework\Cache\Frontend\Decorator\TagScope;
use Magento\Framework\App\Cache\Type\FrontendPool;

class Type extends TagScope
{
    const TYPE_IDENTIFIER = 'structureddatacache_';

    const CACHE_TAG = 'STRUCTUREDDATA';

    public function __construct(
        FrontendPool $cacheFrontendPool
    ){
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}