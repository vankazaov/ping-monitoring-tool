<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class Status
{
    public function __construct(
        private string $domain,
        private int $code,
        private string $status,
        private float $time,
        private float $size)
    {

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


}