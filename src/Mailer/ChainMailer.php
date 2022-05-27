<?php

declare(strict_types=1);

namespace PingMonitoringTool\Mailer;

use Exception;
use PingMonitoringTool\ErrorHandler;

class ChainMailer extends AbstractMailer
{
    private $mailers;
    private $handler;

    public function __construct(ErrorHandler $handler, Mailer ...$mailers)
    {
        $this->handler = $handler;
        $this->mailers = $mailers;
    }

    public function send(): bool
    {
        $send = false;
        foreach ($this->mailers as $mailer)
        {
            try {
                $mailer->setDomain($this->domain);
                $mailer->setTypeMessage($this->typeMessage);
                $mailer->setStatusObject($this->statusObject);
                if ($mailer->send()) return true;
            } catch (Exception $e) {
                $this->handler->handle($e);
            }
        }
        return $send;
    }

    public function sendReport(): bool
    {
        $send = false;
        foreach ($this->mailers as $mailer)
        {
            try {
                $mailer->setDataReport($this->dataReport);
                if ($mailer->sendReport()) return true;
            } catch (Exception $e) {
                $this->handler->handle($e);
            }
        }
        return $send;
    }
}