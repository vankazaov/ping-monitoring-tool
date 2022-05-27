<?php

declare(strict_types=1);

namespace PingMonitoringTool\Mailer;

use PingMonitoringTool\Domain;
use PingMonitoringTool\Status;

interface Mailer
{
    public function send():bool;
    public function sendReport():bool;

    public function setTypeMessage(string $type):void;
    public function setStatusObject(?Status $status):void;
    public function setDomain(Domain $domain):void;
    public function setDataReport(array $dataReport): void;
}