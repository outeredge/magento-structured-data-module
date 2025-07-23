<?php

namespace OuterEdge\StructuredData\Plugin;

use Magento\Framework\View\Element\AbstractBlock;

class EscapeStripTags
{
    public function beforeStripTags(AbstractBlock $subject, $data, $allowableTags = null, $allowHtmlEntities = false)
    {
        if ($data) {
            $data = preg_replace('`<((script)|(style))[^>]*>.*?</\1>`si','', $data);
        }
        return [$data, $allowableTags, $allowHtmlEntities];
    }
}