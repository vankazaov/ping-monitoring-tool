<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PingMonitoringTool\Domain;
use PingMonitoringTool\Status;

class StatusTest extends TestCase
{
    public function testSetAndReadProperties()
    {
        $domain = new Status(
            $host = new Domain('yandex.ru'),
            $datetime = new DateTimeImmutable('2022-05-27'),
            $code = 200,
            $status = 'OK',
            $time = 0.5,
            $size = 12.5
        );

        self::assertEquals($host->getValue(), $domain->getDomain());
        self::assertEquals($datetime->format('Y-m-d'), $domain->getDatetime()->format('Y-m-d'));
        self::assertEquals($code, $domain->getCode());
        self::assertEquals($status, $domain->getStatus());
        self::assertEquals($time, $domain->getTime());
        self::assertEquals($size, $domain->getSize());

    }
}