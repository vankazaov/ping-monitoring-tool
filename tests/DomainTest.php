<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PingMonitoringTool\Domain;

class DomainTest extends TestCase
{

    public function testSuccess(): void
    {
        $domain = new Domain($value = 'yandex.ru');
        self::assertEquals($value, $domain->getValue());
    }

    public function testInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Domain('my site .com');
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Domain('');
    }
}