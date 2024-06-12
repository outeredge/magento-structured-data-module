<?php

namespace OuterEdge\StructuredData\Api;

interface OffersRepositoryInterface
{
    /**
     * @param string $productId
     * @return mixed[]
     */
    public function offers($productId);
}