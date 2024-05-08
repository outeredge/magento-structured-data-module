<?php

namespace OuterEdge\StructuredData\Api;

interface OffersRepositoryInterface
{
    /**
     * @param string $sku
     * @return array
     */
    public function get($sku);
}