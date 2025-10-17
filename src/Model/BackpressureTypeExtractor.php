<?php

namespace Mediarox\Honeypot\Model;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Mediarox\Honeypot\Model\Backpressure\HoneyPotLimitConfigManager;

class BackpressureTypeExtractor
{
    public function __construct(
        private readonly HoneyPotLimitConfigManager $configManager,
        private readonly Configuration              $moduleConfig
    ) {
    }

    /**
     * @inheritDoc
     */
    public function extract(RequestInterface $request, ActionInterface $action): ?string
    {
        $actions = $this->moduleConfig->getActions();
        $fullActionName = $request->getFullActionName();
        $rateLimitAction = \in_array($fullActionName, $actions, true);
        if ($this->configManager->isEnforcementEnabled() && $rateLimitAction) {
            return HoneyPotLimitConfigManager::REQUEST_TYPE_ID;
        }

        return null;
    }
}
