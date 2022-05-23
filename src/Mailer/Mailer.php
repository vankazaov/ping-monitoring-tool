<?php

declare(strict_types=1);

namespace PingMonitoringTool\Mailer;

use PingMonitoringTool\Status;

interface Mailer
{
    public function send():bool;
    public function sendReport():bool;

    public function setTypeMessage(string $type):void;
    public function setStatusObject(?Status $status):void;
    public function setDomain(string $domain):void;
    public function setDataReport(array $dataReport): void;
}