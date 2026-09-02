<?php

namespace Mediarox\Honeypot\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validator\NotEmpty;
use Mediarox\Honeypot\Model\Configuration;

/**
 * Class ControllerActionPredispatchObserver
 *
 * @package Mediarox\Honeypot
 */
class ControllerActionPredispatchObserver implements ObserverInterface
{
    protected RequestInterface $request;

    public function __construct(
        private Configuration       $configuration,
        private NotEmpty            $notEmpty,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws NotFoundException
     */
    public function execute(Observer $observer): void
    {
        if (!$this->configuration->isEnabled()) {
            return;
        }

        /** @var RequestInterface $request */
        $this->request = $observer->getEvent()
            ->getData('request');
        $shouldValidate = $this->shouldValidateRequest();
        if ($shouldValidate && $this->validateRequest()) {
            // Throwing is what stops the action. Returning a Forward result -
            // what this used to do - stops nothing: Event\Invoker\InvokerDefault
            // discards observer return values, so the controller ran to
            // completion and only got a 404 rendered over the top of it.
            // FrontController::dispatch() catches this around processRequest()
            // and forwards to noroute, so the 404 page still renders.
            throw new NotFoundException(__('Honeypot triggered.'));
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
        $emailValid = true;
        if ($params) {
            $honeypotNotEmpty = $this->validateHoneypot($params);
            $timeLimitExceeded = $this->validateTimestamp($params);
            if (isset($params['customer_email'])) {
                $emailValid = $this->validateMail($params);
            }
        }
        return $timeLimitExceeded && $honeypotNotEmpty && !$emailValid;
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
     * @param array $params
     * @return bool
     */
    private function validateTimestamp(array $params): bool
    {
        $timeExceeded = false;
        if (isset($params['timestamp'])) {
            $integerTimestamp = (int)$params['timestamp'];
            $timestamp = $integerTimestamp / 1000;
            $currentTimestamp = time();
            $timeElapsed = $currentTimestamp - $timestamp;
            $timeExceeded = ($timeElapsed < 2);
        }

        return $timeExceeded;
    }

    private function shouldValidateRequest(): bool
    {
        $isPost = $this->request->isPost();
        $fullActionName = $this->request->getFullActionName();
        $allowedAction = \in_array(
            $fullActionName,
            $this->configuration->getActions(),
            true
        );
        return $allowedAction && $isPost;
    }

    private function validateMail(array $params): bool
    {
        $restrictedMail = $this->configuration->getRestrictedMails();
        $emailValid = true;
        $email = $params['customer_email'];
        foreach ($restrictedMail as $key => $value) {
            $emailValid = str_contains($email, $value);
        }
        return $emailValid;
    }
}
