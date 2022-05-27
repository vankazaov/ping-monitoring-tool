<?php

declare(strict_types=1);

namespace PingMonitoringTool\Mailer;

use PingMonitoringTool\Domain;
use PingMonitoringTool\Status;
use stdClass;

class AbstractMailer implements Mailer
{
    /**
     * @var Domain
     */
    protected $domain;
    protected $typeMessage;
    protected $dataReport;
    protected $mailServer;
    protected $statusObject;

    public function __construct(MailServer $mailServer)
    {
        $this->mailServer = $mailServer;
    }

    public function send(): bool { return false;}
    public function sendReport(): bool { return false;}

    public function setTypeMessage(string $type): void
    {
        $this->typeMessage = $type;
    }

    public function setStatusObject(?Status $status): void
    {
        $this->statusObject = $status;
    }

    public function getLetter(): object
    {
        $letter = new stdClass();
        if (is_null($this->statusObject)) {
            if ($this->typeMessage === 'repeat') {
                $letter->subject = 'REPEAT PING ALERT from ';
            } else {
                $letter->subject = 'PING ALERT from ';
            }

            $letter->subject .= $this->mailServer->getName() . ' ';
            $letter->subject .= "[ {$this->domain->getValue()} ]";
            $letter->subject .= " DOWN 0, 0 ms, 0 byte";
            $letter->message = 'FROM: '. $this->mailServer->getName() . PHP_EOL;
            $letter->message .= date('d.m.Y H:i:s') .': ' . $letter->subject . PHP_EOL;
        } else {
            if ($this->typeMessage === 'repeat') {
                $letter->subject = $this->statusObject->getStatus() === 'OK' ? 'PING RECOVERY from ' : 'REPEAT PING ALERT from ';
            } else {
                $letter->subject = $this->statusObject->getStatus() === 'OK' ? 'PING RECOVERY from ' : 'PING ALERT from ';
            }
            $letter->subject .= $this->mailServer->getName() . ' ';
            $letter->subject .= "[ {$this->domain->getValue()} ]";
            $letter->subject .= " {$this->statusObject->getCode()} {$this->statusObject->getStatus()}, {$this->statusObject->getTime()} ms, {$this->statusObject->getSize()} byte";
            $letter->message = 'FROM: '. $this->mailServer->getName() . PHP_EOL;
            $letter->message .= $this->statusObject->getDatetime()->format('d.m.Y H:i:s') .': '
                . $letter->subject . PHP_EOL;
        }

        return $letter;
    }

    public function getReport(array $data): stdClass
    {
        $message = '';
        $week = 0;
        foreach ($data as $domain_data) {
            $week = $domain_data->stats->week;

            $message .= mb_strtoupper($domain_data->domain) . PHP_EOL;
            $message .= "total_checks: " . $domain_data->stats->total_checks . PHP_EOL;
            $message .= "successful_checks: " . $domain_data->stats->all_success . PHP_EOL;
            $message .= "failed_checks: " . $domain_data->stats->all_falls . PHP_EOL;
            $message .= "uptime_percents: " . $domain_data->stats->uptime . PHP_EOL;
            $message .= "Статистика по датам (UP/DOWN):" . PHP_EOL;
            foreach ($domain_data->stats->data as $domain_data_items) {
                $message .= $domain_data_items->date
                    . ': ' . $domain_data_items->success . '/' . $domain_data_items->falls . PHP_EOL;
            }
            $message .= PHP_EOL . PHP_EOL;
        }
        $letter = new stdClass();
        $letter->subject = "PING REPORT WEEK #$week from [{$this->mailServer->getName()}]";
        $letter->message = $message;

        return $letter;
    }

    public function setDomain(Domain $domain): void
    {
       $this->domain = $domain;
    }

    /**
     * @param array $dataReport
     */
    public function setDataReport(array $dataReport): void
    {
        $this->dataReport = $dataReport;
    }


}