<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PingMonitoringTool\Mailer\MailServer;

class MailServerTest extends TestCase
{
    public function testSetAndReadProperties()
    {
        $mailerServer = new MailServer('yandex.ru');
        $mailerServer->setPassword('pass');
        $mailerServer->setUsername('user');
        $mailerServer->setFrom('from');
        $mailerServer->setRecipients(['mail@']);
        $mailerServer->setPort('port');
        $mailerServer->setHost('host');

        self::assertEquals('yandex.ru', $mailerServer->getName());
        self::assertEquals('pass', $mailerServer->getPassword());
        self::assertEquals('user', $mailerServer->getUsername());
        self::assertEquals('from', $mailerServer->getFrom());
        self::assertIsArray($mailerServer->getRecipients());
        self::assertEquals('port', $mailerServer->getPort());
        self::assertEquals('host', $mailerServer->getHost());

    }
}