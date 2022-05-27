<?php

declare(strict_types=1);

namespace PingMonitoringTool\Mailer;

class PhpMailer extends AbstractMailer
{
    public function send(): bool
    {
        ini_set("SMTP", $this->mailServer->getHost());
        ini_set("sendmail_from", $this->mailServer->getFrom());

        $result = false;
        $letter = $this->getLetter();
        return $this->sendFromMail($this->mailServer->getRecipients(), $letter);
    }

    public function sendReport(): bool
    {
        ini_set("SMTP", $this->mailServer->getHost());
        ini_set("sendmail_from", $this->mailServer->getFrom());

        $letter = $this->getReport($this->dataReport);
        return $this->sendFromMail($this->mailServer->getRecipients(), $letter);
    }

    protected function sendFromMail(array $recipients, $letter): bool
    {
        $result = false;
        foreach ($recipients as $to)
        {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/plain; charset=utf-8\r\n"; // кодировка письма
            $headers .= "From: Mera-PING <{$this->mailServer->getFrom()}>\r\n"; // от кого письмо
            $headers .= "To: <$to>\r\n";

            $result = mail($to, $letter->subject, $letter->message, $headers);
        }
        return $result;
    }
}