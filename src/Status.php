<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class Status
{
    private $domain;
    private $datetime;
    private $code;
    private $status;
    private $time;
    private $size;

    public function __construct(
        string $domain,
        \DateTimeImmutable $datetime,
        int $code,
        string $status,
        float $time,
        float $size)
    {

        $this->domain = $domain;
        $this->datetime = $datetime;
        $this->code = $code;
        $this->status = $status;
        $this->time = $time;
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string|null
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return float|null
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * @return float|null
     */
    public function getSize(): float
    {
        return $this->size;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDatetime(): \DateTimeImmutable
    {
        return $this->datetime;
    }


}