<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class VerifiedDomain
{
    private $domain;
    private $success;
    private $falls;
    private $lastAt;
    private $notify_success;
    private $notify_falls;
    private $week;
    private $weekReport;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
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
    public function getSuccess(): int
    {
        return $this->success;
    }

    /**
     * @param int $success
     */
    public function setSuccess(int $success): void
    {
        $this->success = $success;
    }

    /**
     * @return int
     */
    public function getFalls(): int
    {
        return $this->falls;
    }

    /**
     * @param int $falls
     */
    public function setFalls(int $falls): void
    {
        $this->falls = $falls;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getLastAt(): \DateTimeImmutable
    {
       return $this->lastAt;
    }

    /**
     * @param \DateTimeImmutable $lastAt
     */
    public function setLastAt(\DateTimeImmutable $lastAt): void
    {
        $this->lastAt = $lastAt;
    }


    /**
     * @return int
     */
    public function getWeek(): int
    {
        return $this->week;
    }

    /**
     * @param int $week
     */
    public function setWeek(int $week): void
    {
        $this->week = $week;
    }

    /**
     * @return bool
     */
    public function isWeekReport(): bool
    {
        return $this->weekReport;
    }

    /**
     * @param bool $weekReport
     */
    public function setWeekReport(bool $weekReport): void
    {
        $this->weekReport = $weekReport;
    }

    public function increaseFalls()
    {
        $this->falls++;
    }

    public function increaseSuccess()
    {
        $this->success++;
    }

    /**
     * @return int
     */
    public function getNotifySuccess(): int
    {
        return $this->notify_success;
    }

    /**
     * @param int $notify_success
     */
    public function setNotifySuccess(int $notify_success): void
    {
        $this->notify_success = $notify_success;
    }

    /**
     * @return int
     */
    public function getNotifyFalls(): int
    {
        return $this->notify_falls;
    }

    /**
     * @param int $notify_falls
     */
    public function setNotifyFalls(int $notify_falls): void
    {
        $this->notify_falls = $notify_falls;
    }
}