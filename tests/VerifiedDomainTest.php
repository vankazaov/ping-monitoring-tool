<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PingMonitoringTool\Domain;
use PingMonitoringTool\VerifiedDomain;

class VerifiedDomainTest extends TestCase
{
    public function testSetAndReadProperties()
    {
        $domain = new VerifiedDomain(new Domain('yandex.ru'));
        $domain->setNotifyFalls(0);
        $domain->setNotifySuccess(0);
        $domain->setLastAt(new DateTimeImmutable('2022-05-27'));
        $domain->setWeekReport(false);
        $domain->setWeek(0);
        $domain->setFalls(0);
        $domain->setSuccess(0);

        self::assertEquals('yandex.ru', $domain->getDomain());
        self::assertEquals(0, $domain->getNotifyFalls());
        self::assertEquals(0, $domain->getNotifySuccess());
        self::assertEquals('2022-05-27', $domain->getLastAt()->format('Y-m-d'));
        self::assertFalse($domain->isWeekReport());
        self::assertEquals(0, $domain->getWeek());
        self::assertEquals(0, $domain->getFalls());
        self::assertEquals(0, $domain->getSuccess());

        $domain->increaseSuccess();
        $domain->increaseFalls();

        self::assertEquals(1, $domain->getFalls());
        self::assertEquals(1, $domain->getSuccess());
    }
}