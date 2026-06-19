<?php

declare(strict_types=1);

namespace OuterEdge\StructuredData\Block\Jsonld;

/**
 * Request-scoped registry for FAQ Q&A pairs collected from Storyblok content
 * (and any other source) during the current HTTP request. The Jsonld block
 * reads from this collector to emit a single FAQPage entity inside the @graph.
 */
class FaqCollector
{
    /**
     * @var array<int, array{question: string, answer: string}>
     */
    private array $items = [];

    public function addItem(string $question, string $answer): self
    {
        $question = trim($question);
        $answer = trim($answer);

        if ($question === '' || $answer === '') {
            return $this;
        }

        $this->items[] = [
            'question' => $question,
            'answer' => $answer,
        ];

        return $this;
    }

    /**
     * @return array<int, array{question: string, answer: string}>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function hasItems(): bool
    {
        return $this->items !== [];
    }

    public function reset(): self
    {
        $this->items = [];
        return $this;
    }
}