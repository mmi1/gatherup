<?php

declare(strict_types=1);

namespace App\Tests\unit\Service\Notification;

use App\Service\Notification\EmailToSlackNotificationHandler;
use Exception;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Util\Json;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Notifier\Chatter;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Transport\TransportInterface;

class EmailToSlackHandlerTest extends TestCase
{
    private null|EmailToSlackNotificationHandler $testedService;

    protected function setUp(): void
    {
        /** @var MockObject|TransportInterface $transport */
        $transport = $this->getMockBuilder(TransportInterface::class)->getMock();

        /** @phpstan-var MockObject|LoggerInterface $logger */
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $notifier = new Chatter($transport);

        $this->testedService = new EmailToSlackNotificationHandler($notifier, $logger);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->testedService = null;

        parent::tearDown();
    }


    /**
     * @throws Exception
     */
    public function testDataType(): void
    {
        $email = (new Email())
            ->from('hello@example.com')
            ->to('you@example.com')
            ->subject('Time for Symfony Mailer!')
            ->text('This is Symfony Mailer body text')
        ;
        self::assertTrue($this->testedService->send($email));

        $email = (new EmailMessage(new RawMessage('test email message')));
        self::assertTrue($this->testedService->send($email));

        $email = (new RawMessage('test raw message'));
        self::assertTrue($this->testedService->send($email));
    }

    /**
     * @throws Exception
     */
    public function testEmailToSlackNotificationConversion(): void
    {
        $subject = 'Time for Symfony Mailer!';
        $text = 'This is Symfony Mailer body text';
        $email = (new Email())
            ->from('hello@example.com')
            ->to('you@example.com')
            ->subject($subject)
            ->text($text)
        ;

        $actual = EmailToSlackNotificationHandler::fromEmail($email);
        self::assertInstanceOf(MessageInterface::class, $actual);
        self::assertStringContainsString($text, $actual->getSubject());
        self::assertStringContainsString($subject, $actual->getSubject());
        self::assertStringContainsString(PHP_EOL, $actual->getSubject());
    }

    /**
     * @throws Exception
     */
    public function testLogicExceptionWhenNotSupported(): void
    {
        self::expectException(LogicException::class);
        $this->testedService->send(new Json());
    }

    public function testExceptionWhenNoInformationSent(): void
    {
        $email = (new Email())
            ->from('hello@example.com')
            ->to('you@example.com')
        ;

        self::expectException(Exception::class);
        $this->testedService->send($email);
    }

    public function testExceptionWhenWrongObjectType(): void
    {
        $wrongTypeObject = new Json();

        self::expectException(Exception::class);
        EmailToSlackNotificationHandler::fromEmail($wrongTypeObject);
    }
}
