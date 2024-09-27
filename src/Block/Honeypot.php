<?php

namespace Mediarox\Honeypot\Block;

use Magento\Framework\View\Element\Template;
use Mediarox\Honeypot\Model\Configuration;

/**
 * Class Honeypot
 *
 * @package Mediarox\Honeypot
 */
class Honeypot extends Template
{
    /**
     * @param Template\Context $context
     * @param Configuration $configuration
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        private Configuration $configuration,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @inheritdoc
     */
    public function toHtml()
    {
        if (!$this->configuration->isEnabled()) {
            return '';
        }

        return parent::toHtml();
    }

    /**
     * @return mixed
     */
    public function getFieldName()
    {
        return $this->configuration->getFieldName();
    }

    /**
     * @return mixed
     */
    public function getFieldClass()
    {
        return $this->configuration->getFieldClass();
    }
}
