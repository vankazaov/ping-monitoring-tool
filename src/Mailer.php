<?php

namespace PingMonitoringTool;

class Mailer
{
    private $smtp_host_base;
    private $smtp_host_reserve;
    private $smtp_port_reserve;
    private $smtp_username_reserve;
    private $smtp_password_reserve;
    private $from;
    private $from_server;
    private $repeat_down;
    private $repeat_down_every_minutes;
    private $recipients;
    private $master_server;
    private $mode;
    private $repository;
    private $mailSMTP;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        foreach ($this->repository->getConfig() as $object) {
            if (property_exists($this, $param = $object->parameter)) {
                if (is_array($value_array = json_decode($object->value, true))) {
                    $this->{$param} = $value_array;
                } else {
                    $this->{$param} = $object->value;
                }
            }
        }
        $this->mailSMTP = new SendMailSmtpClass(
            $this->smtp_username_reserve,
            $this->smtp_password_reserve,
            $this->smtp_host_reserve,
            'Mera-TSP PING',
            $this->smtp_port_reserve);
    }

    public function sendMessage()
    {

        if (!$this->canSend()) {
            return false;
        }
        $letters = $this->prepareLetters();
        $this->send($letters);
    }

    private function send($letters)
    {
        if (!$this->canSend()) {
            return false;
        }
        if ($this->isAvailableBaseMailServer($this->smtp_host_base)) {
            $this->sendViaBase($letters);
        } else {
            $this->sendViaReserve($letters);
        }
    }

    private function canSend()
    {
        // Я мастер?
        if ($this->mode === 'master') {
            return true;
        }

        // Мастер is Down?
        $server_id = $this->repository->getServerIdByName($this->master_server);

        if ($this->repository->isDown($server_id)) {
            return true;
        }

        return false;
    }

    private function isAvailableBaseMailServer($smtp_host_base)
    {
        $response = (new Controller())->pingDomain($smtp_host_base);
        if($response['code']>=200 && $response['code']<300 && $response['size'] > 0) {
           return true;
        }
        return false;
    }

    private function prepareLetters()
    {
        $letters = [];
        foreach ($this->repository->getNotNotifyMonitoring() as $server) {
            $server->response = json_decode($server->response);
            $subject = $server->response->status === 'OK' ? 'PING RECOVERY ' : 'PING ALERT ';
            $subject .= "[{$server->response->host}]";
            $subject .= " {$server->response->code} {$server->response->status}, {$server->response->time} ms, {$server->response->size} byte";
            $message = 'FROM: '. $this->from_server . PHP_EOL;
            $message .= $server->datetime .': '. $subject . PHP_EOL;

            $letter = [];
            $letter['server_id'] = $server->server_id;
            $letter['subject'] = $subject;
            $letter['message'] = $message;
            $letter['log'] = $this->repository->getLog($server->server_id);
            $letters[] = $letter;
        }
        return $letters;
    }

    private function sendViaBase(array $letters)
    {
        // Using the ini_set()
        ini_set("SMTP", $this->smtp_host_base);
        ini_set("sendmail_from", $this->from);
        //ini_set("smtp_port", "25");

        foreach ($this->recipients as $to) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/plain; charset=utf-8\r\n"; // кодировка письма
            $headers .= "From: $this->from\r\n"; // от кого письмо
            $headers .= "To: $to\r\n";
            foreach ($letters as $letter) {
                $message = $letter['message'] . PHP_EOL;
                if ($letter['log']) {
                    $message .= 'Log:' . PHP_EOL . $letter['log'];
                }
                $result = mail($to, $letter['subject'], $message, $headers);
                if ($result === true) {
                    $this->setSended($letter['server_id'] ?? null, $letter['report_date'] ?? null);
                } else {
                    $headers= "MIME-Version: 1.0\r\n";
                    $headers .= "Content-type: text/plain; charset=utf-8\r\n"; // кодировка письма
                    $headers .= "From: Mera-PING <{$this->smtp_username_reserve}>\r\n"; // от кого письмо
                    $headers .= "To: $to\r\n";
                    $result = $this->mailSMTP->send($to, $letter['subject'], $message, $headers);
                    if ($result === true) {
                        $this->setSended($letter['server_id'] ?? null, $letter['report_date'] ?? null);
                    }
                }
            }
        }
    }

    private function sendViaReserve(array $letters)
    {
        try {
            foreach ($this->recipients as $to) {
                $headers= "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/plain; charset=utf-8\r\n"; // кодировка письма
                $headers .= "From: Mera-PING <{$this->smtp_username_reserve}>\r\n"; // от кого письмо
                $headers .= "To: $to\r\n";

                foreach ($letters as $letter) {
                    $message = $letter['message'] . PHP_EOL;
                    if ($letter['log']) {
                        $message .= 'Log:' . PHP_EOL . $letter['log'];
                    }
                    $result = $this->mailSMTP->send($to, $letter['subject'], $message, $headers);
                    if ($result === true) {
                        $this->setSended($letter['server_id'] ?? null, $letter['report_date'] ?? null);
                    }
                }
            }
        } catch (\Exception $e)
        {
            throw new \DomainException($e->getMessage());
        }

    }

    private function setSended($server_id, $datereport)
    {
        if ($server_id) {
            $this->repository->setSenderNotify($server_id);
        }
        if ($datereport) {
            $this->repository->setSenderReport($datereport);
        }
    }


    public function sendRepeatLetter()
    {
        if (!$this->repeat_down) return;

        foreach ($this->repository->getRepeatNotifyMonitoring() as $server) {
            // Получим последнюю запись из лога
            $lastLog = $this->repository->getLastLog($server->server_id);
            if ($lastLog) {
                $timeLog = (new \DateTimeImmutable($lastLog->datetime))->getTimestamp();
                $diff = (int) round((time() - $timeLog)/60);
                if ($diff > 0 && $diff % $this->repeat_down_every_minutes === 0) {
                    $this->repository->setNotSenderNotify($server->server_id);
                }
            }
        }
    }

    public function sendWeekReport()
    {
        $start_week = date("Y-m-d", strtotime('monday previous week'));
        $end_week = date("Y-m-d", strtotime('sunday previous week'));
        $week = date("W", strtotime('monday previous week'));
        $getLastReport = $this->repository->getWeekReport($start_week);
        if ($getLastReport) return;

        $letters = [];
        $letter['subject'] = "PING REPORT WEEK #$week from [$this->from_server]";
        $letter['log'] = '';
        $letter['report_date'] = $start_week;
        $calculateData = $this->repository->calcUptime($start_week, $end_week);
        if (empty($calculateData)) return;

        $message = "Неделя №$week. Еженедельный отчет о доступности серверов отправленный из {$this->from_server} \n\n";
        foreach ($calculateData as $item) {
            $message .= $item['server_name'] . PHP_EOL;
            $message .= "total_checks: " . $item['total_checks'] . PHP_EOL;
            $message .= "successful_checks: " . $item['successful_checks'] . PHP_EOL;
            $message .= "failed_checks: " . $item['failed_checks'] . PHP_EOL;
            $message .= "uptime_percents: " . $item['uptime_percents'] . PHP_EOL . PHP_EOL;
        }
        $letter['message'] = $message;
        $letters[] = $letter;

        $this->send($letters);
    }

}