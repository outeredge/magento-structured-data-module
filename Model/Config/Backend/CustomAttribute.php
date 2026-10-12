<?php

namespace OuterEdge\StructuredData\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\SerializerInterface;

class CustomAttribute extends ConfigValue
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param SerializerInterface $serializer
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        SerializerInterface $serializer,
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->serializer = $serializer;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Validate and serialize attribute rows before save
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        $value = $this->getValue();

        if (is_string($value) && $value) {
            try {
                $value = $this->serializer->unserialize($value);
            } catch (\InvalidArgumentException $e) {
                $value = [];
            }
        }

        $rows = [];
        $jsonKeys = [];

        foreach ((array)$value as $row) {
            if (!is_array($row)) {
                continue;
            }

            $attributeCode = trim((string)($row['attribute_code'] ?? ''));
            $jsonKey = trim((string)($row['json_key'] ?? ''));

            if ($attributeCode === '' && $jsonKey === '') {
                continue;
            }

            if ($attributeCode === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $attributeCode)) {
                throw new LocalizedException(__('Invalid product attribute code "%1".', $attributeCode));
            }

            if ($jsonKey === '') {
                $jsonKey = $attributeCode;
            }

            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $jsonKey)) {
                $message = __(
                    'Invalid JSON property name "%1" for attribute "%2". Use letters, numbers and underscores only.',
                    $jsonKey,
                    $attributeCode
                );
                throw new LocalizedException($message);
            }

            if (isset($jsonKeys[$jsonKey])) {
                throw new LocalizedException(__('JSON property name "%1" is used more than once.', $jsonKey));
            }

            $jsonKeys[$jsonKey] = true;
            $rows[] = ['attribute_code' => $attributeCode, 'json_key' => $jsonKey];
        }

        $this->setValue($this->serializer->serialize($rows));

        return $this;
    }

    /**
     * Unserialize stored value after load
     *
     * @return $this
     */
    protected function _afterLoad()
    {
        $value = $this->getValue();

        if (is_string($value) && $value) {
            try {
                $this->setValue($this->serializer->unserialize($value));
            } catch (\InvalidArgumentException $e) {
                $this->setValue([]);
            }
        }

        return $this;
    }
}
