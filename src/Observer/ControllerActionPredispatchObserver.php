<?php

namespace Mediarox\Honeypot\Observer;

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
        private ForwardFactory $forwardFactory
    ) {
    }

    /**
     * @param Observer $observer
     * @return void|Forward
     */
    public function execute(Observer $observer)
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        /** @var RequestInterface $request */
        $request = $observer->getEvent()->getData('request');

        if ($this->shouldValidateRequest($request)
            && !$this->validateRequest($request)
        ) {
            return $this->forwardFactory->create()->forward('noroute');
        }
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    private function shouldValidateRequest(RequestInterface $request)
    {
        if (in_array($request->getFullActionName(), $this->configuration->getActions())) {
            return true;
        }

        return false;
    }

    /**
     * Validate that the honeypot field is present in request and that field is empty.
     *
     * @param RequestInterface $request
     * @return bool
     */
    private function validateRequest(RequestInterface $request)
    {
        $field = $this->configuration->getFieldName();
        $params = $request->getParams();
        $notEmpty = new \Magento\Framework\Validator\NotEmpty();

        if (!isset($params[$field])
            || $notEmpty->isValid(trim($request->getParam($field)))
        ) {
            return false;
        }

        return true;
    }
}
