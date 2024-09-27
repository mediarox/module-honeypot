<?php

namespace Mediarox\Honeypot\Observer;

use Magento\Framework\Validator\NotEmpty;
use Mediarox\Honeypot\Model\Configuration;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class ControllerActionPredispatchObserver
 *
 * @package Mediarox\Honeypot
 */
class ControllerActionPredispatchObserver implements ObserverInterface
{
    public function __construct(
        private Configuration $configuration,
        private ForwardFactory $forwardFactory,
        private NotEmpty $notEmpty
    ) {
    }

    /**
     * @param  Observer $observer
     * @return void|Forward
     */
    public function execute(Observer $observer)
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        /** @var RequestInterface $request */
        $request = $observer->getEvent()->getData('request');

        if ($this->validateRequest($request)) {
            return $this->forwardFactory->create()->forward('noroute');
        }
    }

    /**
     * Validate that the honeypot field is present in request and that field is
     * empty.
     *
     * @param  RequestInterface $request
     * @return bool
     */
    private function validateRequest(RequestInterface $request): bool
    {
        $field = $this->configuration->getFieldName();
        $params = $request->getParams();

        return isset($params[$field]) && $this->notEmpty->isValid(trim($params[$field]));
    }
}
