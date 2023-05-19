<?php

declare(strict_types=1);

namespace App\Service\Notification;

use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\MessageInterface;

class EmailToSlackNotificationHandler
{
    public const SUPPORTED_TYPES = [
        Email::class,
        EmailMessage::class,
        RawMessage::class
    ];

    public function __construct(
        private readonly ChatterInterface $notifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws Exception
     */
    public function send(object $message): bool
    {
        if (!$this->supports($message)) {
            throw new LogicException('Email message type is not supported.');
        }

        $notification = self::fromEmail($message);

        try {
            $this->notifier->send($notification);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }

        return true;
    }

    /**
     * @throws Exception
     */
    public static function fromEmail(object $message): MessageInterface
    {
        $body = [];

        switch (true) {
            case ($message instanceof Email):
                /** @phpstan-var Email $message */
                $subject = $message->getSubject();
                $content = $message->getTextBody();
                break;

            case ($message instanceof EmailMessage):
                /** @phpstan-var EmailMessage $message */
                $subject = $message->getSubject();
                $content = $message->getMessage()->toString();
                break;

            case ($message instanceof RawMessage):
                /** @phpstan-var RawMessage $message */
                $subject = 'New Message';
                $content = $message->toString();
                break;

            default:
                throw new Exception('Improper email message object type.');
        }

        if (null === $subject && null === $content) {
            throw new Exception('Message object does not contain any information.');
        }

        if (null !== $subject) {
            $body[] = "*$subject*";
        }
        if (null !== $content) {
            $body[] = $content;
        }

        return new ChatMessage(implode(PHP_EOL, $body));
    }

    private function supports(object $message): bool
    {
        foreach (self::SUPPORTED_TYPES as $type) {
            if ($message instanceof $type) {
                return true;
            }
        }

        return false;
    }
}
