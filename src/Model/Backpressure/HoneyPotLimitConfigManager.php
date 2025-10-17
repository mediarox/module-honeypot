<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mediarox\Honeypot\Model\Backpressure;

use Magento\Framework\App\Backpressure\ContextInterface;
use Magento\Framework\App\Backpressure\SlidingWindow\LimitConfig;
use Magento\Framework\App\Backpressure\SlidingWindow\LimitConfigManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Store\Model\ScopeInterface;

/**
 * Provides backpressure limits for ordering
 */
class HoneyPotLimitConfigManager implements LimitConfigManagerInterface
{
    public const REQUEST_TYPE_ID = 'honeypot';

    public function __construct(private readonly ScopeConfigInterface $config)
    {
    }

    /**
     * @inheritDoc
     *
     * @throws RuntimeException
     */
    public function readLimit(ContextInterface $context): LimitConfig
    {
        switch ($context->getIdentityType()) {
            case ContextInterface::IDENTITY_TYPE_ADMIN:
            case ContextInterface::IDENTITY_TYPE_CUSTOMER:
                $limit = $this->fetchAuthenticatedLimit();
                break;
            case ContextInterface::IDENTITY_TYPE_IP:
                $limit = $this->fetchGuestLimit();
                break;
            default:
                throw new RuntimeException(__("Identity type not found"));
        }

        return new LimitConfig($limit, $this->fetchPeriod());
    }

    /**
     * Checks if enforcement enabled for the current store
     *
     * @return bool
     */
    public function isEnforcementEnabled(): bool
    {
        return $this->config->isSetFlag('sales/backpressure/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Limit for authenticated customers
     *
     * @return int
     */
    private function fetchAuthenticatedLimit(): int
    {
        return (int)$this->config->getValue('sales/backpressure/limit', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Limit for guests
     *
     * @return int
     */
    private function fetchGuestLimit(): int
    {
        return (int)$this->config->getValue(
            'sales/backpressure/guest_limit',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Counter reset period
     *
     * @return int
     */
    private function fetchPeriod(): int
    {
        return (int)$this->config->getValue('sales/backpressure/period', ScopeInterface::SCOPE_STORE);
    }
}
