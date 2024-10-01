<?php

namespace Mediarox\Honeypot\Observer;

use Magento\Framework\Serialize\SerializerInterface;
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
    protected RequestInterface $request;

    public function __construct(
        private Configuration $configuration,
        private ForwardFactory $forwardFactory,
        private NotEmpty $notEmpty,
        private SerializerInterface $serializer
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
        $this->request = $observer->getEvent()
            ->getData('request');
        $isPost = $this->request->isPost();
        if ($isPost && $this->validateRequest()) {
            return $this->forwardFactory->create()
                ->forward('noroute');
        }
    }

    /**
     * Validate that the honeypot field is present in request, that field is
     * empty and that the execution time is not bot related.
     *
     * @return bool
     */
    private function validateRequest(): bool
    {
        $params = $this->request->getParams() ?? [];
        if ($this->request->isAjax()) {
            $content = $this->request->getContent();
            $content = is_string($content) ? $this->serializer->unserialize(
                $content
            ) : [];
            $params = array_merge($params, $content);
        }
        $timeLimitExceeded = false;
        $honeypotNotEmpty = false;
        if ($params) {
            $honeypotNotEmpty = $this->validateHoneypot($params);
            $timeLimitExceeded = $this->validateTimestamp($params);
        }
        return $timeLimitExceeded && $honeypotNotEmpty;
    }

    /**
     * Validate that the honeypot field is present in request and that field is
     * empty.
     */
    private function validateHoneypot(array $params): bool
    {
        $field = $this->configuration->getFieldName();

        return isset($params[$field]) && $this->notEmpty->isValid(
            trim($params[$field])
        );
    }

    /**
     * Validate execution time for form action
     *
     * @param  array $params
     * @return bool
     */
    private function validateTimestamp(array $params): bool
    {
        $timeExceeded = false;
        if (isset($params['timestamp'])) {
            $timestamp = $params['timestamp'] / 1000;
            $currentTimestamp = time();
            $timeElapsed = $currentTimestamp - $timestamp;
            $timeExceeded = ($timeElapsed < 2);
        }

        return $timeExceeded;
    }
}
