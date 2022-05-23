<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PingMonitoringTool\Domain;
use PingMonitoringTool\HttpClient;
use PingMonitoringTool\Monitoring;

class MonitoringTest extends TestCase
{

    public function testSuccess(): void
    {

        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn([
            'domain' => 'yandex.ru',
            'code' => 200,
            'code_definition' => 'OK',
            'time' => 0.25,
            'size' => 125,
        ]);

        $monitor = new Monitoring($client);

        $status = $monitor->ping(new Domain("yandex.ru"));
        self::assertNotNull($status);
        self::assertEquals('yandex.ru', $status->getDomain());
        self::assertEquals(200, $status->getCode());
        self::assertEquals('OK', $status->getStatus());
        self::assertEquals(0.25, $status->getTime());
        self::assertEquals(125, $status->getSize());
    }

    public function testNotFound(): void
    {
        $client = $this->createMock(HttpClient::class);
        $client->method('get')->willReturn([
            'domain' => 'yandex.ru',
            'code' => 0,
            'code_definition' => 'DOWN',
            'time' => 0,
            'size' => 0,
        ]);

        $monitor = new Monitoring($client);
        $status = $monitor->ping(new Domain("localhost.com"));
        self::assertNull($status);
    }
}