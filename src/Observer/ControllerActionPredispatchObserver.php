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
 * @package Mediarox_Honeypot
 */
class ControllerActionPredispatchObserver implements ObserverInterface
{
    protected RequestInterface $request;

    public function __construct(
        private Configuration $configuration,
        private NotEmpty $notEmpty,
        private SerializerInterface $serializer
    ) {
    }

    /**
     * @param  Observer $observer
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

        if (!$this->shouldValidateRequest() || !$this->isSpam()) {
            return;
        }

        // Throwing is what actually stops the action. FrontController::dispatch()
        // catches NotFoundException around processRequest(), forwards to noroute
        // and re-enters the loop, so the visitor still gets the 404 page.
        //
        // Returning a Forward result - what this observer used to do - stops
        // nothing: Event\Invoker\InvokerDefault discards observer return values,
        // and Forward::forward() only rewrites the request. The controller ran
        // to completion, saved the spam, and merely had a 404 rendered over the
        // top of it. Setting ActionInterface::FLAG_NO_DISPATCH instead does not
        // work either: the flag is looked up under the action name current at
        // read time, and forward() has already changed that to "noroute".
        throw new NotFoundException(__('Honeypot triggered.'));
    }

    /**
     * The honeypot field is hidden via CSS, so a human never sees it. Anything
     * in there is conclusive on its own, and it is deliberately not combined
     * with a second condition: every condition ANDed onto it is one more way
     * past the honeypot. The previous version also required the form to be
     * submitted within two seconds, so waiting was enough to get through.
     *
     * @return bool
     */
    private function isSpam(): bool
    {
        $params = $this->request->getParams() ?? [];
        if ($this->request->isAjax()) {
            $content = $this->request->getContent();
            $content = is_string($content) ? $this->serializer->unserialize(
                $content
            ) : [];
            $params = array_merge($params, $content);
        }

        return $params && $this->isHoneypotFilled($params);
    }

    /**
     * Validate that the honeypot field is present in the request and filled in.
     */
    private function isHoneypotFilled(array $params): bool
    {
        $field = $this->configuration->getFieldName();

        return isset($params[$field]) && $this->notEmpty->isValid(
            trim($params[$field])
        );
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
}
