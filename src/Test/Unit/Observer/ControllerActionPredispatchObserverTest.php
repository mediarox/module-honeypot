<?php

declare(strict_types=1);

namespace Mediarox\Honeypot\Test\Unit\Observer;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validator\NotEmpty;
use Mediarox\Honeypot\Model\Configuration;
use Mediarox\Honeypot\Observer\ControllerActionPredispatchObserver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ControllerActionPredispatchObserverTest extends TestCase
{
    private const string GUARDED_ACTION = 'xnotif_email_stockGuest';
    private const string FIELD = 'url';

    private Configuration&MockObject $configuration;
    private ControllerActionPredispatchObserver $observer;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('isEnabled')->willReturn(true);
        $this->configuration->method('getActions')->willReturn([self::GUARDED_ACTION]);
        $this->configuration->method('getFieldName')->willReturn(self::FIELD);

        $this->observer = new ControllerActionPredispatchObserver(
            $this->configuration,
            new NotEmpty(),
            $this->createMock(SerializerInterface::class)
        );
    }

    /**
     * @param array<string, mixed> $params
     */
    private function observerFor(
        array $params,
        string $action = self::GUARDED_ACTION,
        bool $isPost = true
    ): Observer {
        $request = $this->createMock(HttpRequest::class);
        $request->method('getParams')->willReturn($params);
        $request->method('isAjax')->willReturn(false);
        $request->method('isPost')->willReturn($isPost);
        $request->method('getFullActionName')->willReturn($action);

        return new Observer(['event' => new Event(['request' => $request])]);
    }

    /**
     * Blocking means throwing: FrontController::dispatch() catches
     * NotFoundException around processRequest() and forwards to noroute, so the
     * action never runs. Returning a Forward result stops nothing at all.
     */
    private function expectBlocked(): void
    {
        $this->expectException(NotFoundException::class);
    }

    private function expectPassedThrough(): void
    {
        // Passing through is the absence of the exception above.
        $this->expectNotToPerformAssertions();
    }

    public function testStopsTheActionWhenTheHoneypotFieldIsFilled(): void
    {
        $this->expectBlocked();

        $this->observer->execute($this->observerFor([self::FIELD => 'http://spam.example']));
    }

    public function testBlocksRegardlessOfHowLongTheVisitorTook(): void
    {
        // The hidden field is conclusive on its own. Anything ANDed onto it is
        // another way past the honeypot — a bot only has to wait.
        $this->expectBlocked();

        $this->observer->execute(
            $this->observerFor([
                self::FIELD => 'http://spam.example',
                'timestamp' => (string)((time() - 3600) * 1000),
            ])
        );
    }

    public function testLetsAnEmptyHoneypotFieldThrough(): void
    {
        $this->expectPassedThrough();

        $this->observer->execute($this->observerFor([self::FIELD => '   ', 'guest_email' => 'a@b.de']));
    }

    public function testLetsARequestWithoutTheFieldThrough(): void
    {
        $this->expectPassedThrough();

        $this->observer->execute($this->observerFor(['guest_email' => 'a@b.de']));
    }

    public function testIgnoresActionsThatAreNotConfigured(): void
    {
        $this->expectPassedThrough();

        $this->observer->execute(
            $this->observerFor([self::FIELD => 'spam'], 'checkout_cart_index')
        );
    }

    public function testIgnoresRequestsThatAreNotPosts(): void
    {
        $this->expectPassedThrough();

        $this->observer->execute(
            $this->observerFor([self::FIELD => 'spam'], self::GUARDED_ACTION, false)
        );
    }
}
