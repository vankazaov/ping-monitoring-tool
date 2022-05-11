<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PingMonitoringTool\Domain;
use PingMonitoringTool\Monitoring;

class MonitoringTest extends TestCase
{

    public function testSuccess(): void
    {
        $monitor = new Monitoring();

        $status = $monitor->ping(new Domain("mera-device.com"));
        self::assertNotNull($status);
        self::assertEquals('mera-device.com', $status->getDomain());
        self::assertEquals('200', $status->getCode());
        self::assertEquals('OK', $status->getStatus());
        self::assertNotNull($status->getTime());
        self::assertNotNull($status->getSize());
    }

    public function testNotFound(): void
    {
        $monitor = new Monitoring();
        $status = $monitor->ping(new Domain("localhost.com"));
        self::assertNull($status);
    }
}