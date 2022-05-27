<?php

declare(strict_types=1);

namespace PingMonitoringTool\Mailer;

use Exception;

class SmtpMailer extends AbstractMailer
{
    public function send(): bool
    {
        $letter = $this->getLetter();
        $result = false;
        foreach ($this->mailServer->getRecipients() as $to)
        {
            $contentMail = $this->getContentMail($to, $letter);

            try {
                $result = $this->sendBySmtp($to, $contentMail);
            } catch (Exception $e) {
            }

        }
        return $result;
    }

    public function sendReport(): bool
    {
        $letter = $this->getReport($this->dataReport);
        $result = false;
        foreach ($this->mailServer->getRecipients() as $to)
        {
            $contentMail = $this->getContentMail($to, $letter);

            try {
                $result = $this->sendBySmtp($to, $contentMail);
            } catch (Exception $e) {
            }

        }
        return $result;
    }

    /**
     * @throws Exception
     */
    private function sendBySmtp(string $mailTo, string $contentMail): bool
    {
        if(!$socket = @fsockopen($this->mailServer->getHost(), (int) $this->mailServer->getPort(), $errorNumber, $errorDescription, 30)){
            throw new Exception($errorNumber.".".$errorDescription);
        }
        if (!$this->_parseServer($socket, "220")){
            throw new Exception('Connection error');
        }

        $server_name = $_SERVER["SERVER_NAME"] ?? php_uname("n");
        fputs($socket, "HELO $server_name\r\n");
        if (!$this->_parseServer($socket, "250")) {
            fclose($socket);
            throw new Exception('Error of command sending: HELO');
        }

        fputs($socket, "AUTH LOGIN\r\n");
        if (!$this->_parseServer($socket, "334")) {
            fclose($socket);
            throw new Exception('Autorization error');
        }



        fputs($socket, base64_encode($this->mailServer->getUsername()) . "\r\n");
        if (!$this->_parseServer($socket, "334")) {
            fclose($socket);
            throw new Exception('Autorization error');
        }

        fputs($socket, base64_encode($this->mailServer->getPassword()) . "\r\n");
        if (!$this->_parseServer($socket, "235")) {
            fclose($socket);
            throw new Exception('Autorization error');
        }

        fputs($socket, "MAIL FROM: <".$this->mailServer->getUsername().">\r\n");
        if (!$this->_parseServer($socket, "250")) {
            fclose($socket);
            throw new Exception('Error of command sending: MAIL FROM');
        }

        $mailTo = ltrim($mailTo, '<');
        $mailTo = rtrim($mailTo, '>');
        fputs($socket, "RCPT TO: <" . $mailTo . ">\r\n");
        if (!$this->_parseServer($socket, "250")) {
            fclose($socket);
            throw new Exception('Error of command sending: RCPT TO');
        }

        fputs($socket, "DATA\r\n");
        if (!$this->_parseServer($socket, "354")) {
            fclose($socket);
            throw new Exception('Error of command sending: DATA');
        }

        fputs($socket, $contentMail."\r\n.\r\n");
        if (!$this->_parseServer($socket, "250")) {
            fclose($socket);
            throw new Exception("E-mail didn't sent");
        }

        fputs($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    }

    private function _parseServer($socket, string $response): bool
    {
        $responseServer = null;
        while (!is_string($responseServer) || @substr($responseServer, 3, 1) != ' ') {
            if (!($responseServer = fgets($socket, 256))) {
                return false;
            }
        }

        if (!(substr($responseServer, 0, 3) == $response)) {
            return false;
        }
        return true;
    }

    /**
     * @param $to
     * @param \stdClass $letter
     * @return string
     */
    protected function getContentMail($to, \stdClass $letter): string
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=utf-8\r\n"; // кодировка письма
        $headers .= "From: Mera-PING <{$this->mailServer->getUsername()}>\r\n"; // от кого письмо
        $headers .= "To: <$to>\r\n";

        $contentMail = "Date: " . date("D, d M Y H:i:s") . " UT\r\n";
        $contentMail .= 'Subject: =?utf-8?B?' . base64_encode($letter->subject) . "=?=\r\n";
        $contentMail .= $headers . "\r\n";
        $contentMail .= $letter->message . "\r\n";
        return $contentMail;
    }
}