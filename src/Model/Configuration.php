<?php

namespace Mediarox\Honeypot\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Configuration
 *
 * @package Mediarox\Honeypot
 */
class Configuration
{
    const XML_PATH_ENABLE = 'system/honeypot/enable';
    const XML_PATH_FIELD_NAME = 'system/honeypot/field_name';
    const XML_PATH_FIELD_CLASS = 'system/honeypot/field_class';

    /**
     * Configuration constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param string $scopeType
     * @param null $scopeCode
     * @return mixed
     */
    public function isEnabled($scopeType = 'store', $scopeCode = null)
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE, $scopeType, $scopeCode);
    }

    /**
     * @param string $scopeType
     * @param null $scopeCode
     * @return mixed
     */
    public function getFieldName($scopeType = 'store', $scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FIELD_NAME, $scopeType, $scopeCode);
    }

    /**
     * @param string $scopeType
     * @param null $scopeCode
     * @return mixed
     */
    public function getFieldClass($scopeType = 'store', $scopeCode = null)
    {
        return $this->scopeConfig->getValue(self::XML_PATH_FIELD_CLASS, $scopeType, $scopeCode);
    }
}
