<?php

namespace PingMonitoringTool;

class Controller
{
    public static $debug = false;
    private static $sapi = false;
    private $repository;
    private $mailer;
    private $http_codes;
    private $delay_down;

    public function __construct()
    {
        new Setup();
        if (Setup::$ready) {
            self::$sapi = php_sapi_name();
            $this->http_codes =  parse_ini_file(__DIR__ . '/status_code_definitions.ini');
            $this->repository = new Repository();
            $this->mailer = new Mailer($this->repository);
            $this->delay_down = $this->repository->getConfigParam('delay_down');
        }
    }

    public function run()
    {
        $this->debugMessage('Начало проверки серверов');
        date_default_timezone_set('Europe/Moscow');
        foreach ($this->repository->getServers() as $server) {
            $this->debugMessage("Проверяется: {$server->server_name}");
            $response = $this->pingDomain($server->server_name);
            $log_message = sprintf("%s %s %s: %s ms, %s byte",
                $server->server_name, $response['code'], $response['status'],
                $response['time'], $response['size']);
            $this->repository->writeLog($server->server_id, $response['code'], $log_message);

            if($response['code']>=200 && $response['code']<300 && $response['size'] > 0) {
                $this->setUp($server->server_id, $response);
            } else {
                $this->setDown($server->server_id, $response);
            }
        }
        $this->debugMessage("Проверяем, что сообщение было 2 раза подряд.");
        if ($this->isTwice()) {
            $this->debugMessage("Можно отправлять письмо.");
            $this->mailer->sendMessage();
            $this->mailer->sendRepeatLetter();
        } else {
            $this->debugMessage("Отправлять не нужно.");
        }

        $this->mailer->sendWeekReport();
    }

    public function pingDomain($host, $down_count = 0){
        $chTest = curl_init();
        curl_setopt($chTest, CURLOPT_URL, $host);
        curl_setopt($chTest, CURLOPT_HEADER, true);
        curl_setopt($chTest, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chTest, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($chTest, CURLOPT_CONNECTTIMEOUT, 3);
        curl_exec($chTest);
        $codeTest = curl_getinfo($chTest, CURLINFO_HTTP_CODE);
        $codeDefinition = $this->http_codes[$codeTest] ?? 'DOWN';
        $sizeTest = (int) curl_getinfo($chTest,  CURLINFO_SIZE_DOWNLOAD);
        $totalTime = curl_getinfo($chTest,   CURLINFO_TOTAL_TIME);
        curl_close($chTest);
        if ($codeDefinition === 'DOWN' && $down_count < 3) {
            $down_count++;
            sleep($this->delay_down);
            $this->pingDomain($host, $down_count);
        }

        return [
            'code' => (int) $codeTest,
            'size' => (float) $sizeTest,
            'time' => $totalTime,
            'status' => $codeDefinition,
            'host' => $host,
        ];
    }

    public static function debugMessage(string $message, bool $die = false)
    {
        if (!self::$debug) {
            return false;
        }
        $eol = null;
        if (self::$sapi === 'cli') {
            $eol = PHP_EOL;
        } else {
            $eol = "<br>\n";
        }
        $currenttime = date('Y-m-d H:i:s');
        echo "$currenttime: $message $eol";
        if ($die) die('stop programm!');
    }

    private function setUp($server_id, array $response)
    {
        if (!$this->repository->isUp($server_id)) {
            $this->debugMessage("Сервер был в статусе DOWN.");
            $current_time = date("Y-m-d H:i:s");
            $this->debugMessage("Удаляем все записи в `monitoring` с ID сервера: $server_id.");
            $this->repository->clearMonitoring($server_id);
            $this->debugMessage("Вставляем запись в `monitoring` с ID сервера: $server_id, статус: 1, время: $current_time, уведомление: 0.");
            $this->repository->insertMonitoringServer($server_id, 1, $current_time, $response, 0);
        } else {
            $this->debugMessage("Сервер был в статусе UP. Ничего не делаем.");
        }
    }

    private function setDown($server_id, array $response)
    {
        if (!$this->repository->isDown($server_id)) {
            $this->debugMessage("Сервер был в статусе UP.");
            $current_time = date("Y-m-d H:i:s");
            $this->debugMessage("Удаляем все записи в `monitoring` с ID сервера: $server_id.");
            $this->repository->clearMonitoring($server_id);
            $this->debugMessage("Вставляем запись в `monitoring` с ID сервера: $server_id, статус: 0, время: $current_time, уведомление: 0.");
            $this->repository->insertMonitoringServer($server_id, 0, $current_time, $response, 0);
        } else {
            $this->debugMessage("Сервер был в статусе DOWN. Ничего не делаем.");
        }
    }

    private function isTwice()
    {
        $not_notify = $this->repository->getNotNotifyMonitoring();
        $week = date("Y-m-d", strtotime('today'));
        foreach ($not_notify as $server) {
            $mode = 'down';
            if ($server->status) {
                $mode = 'up';
            }
            $rows = $this->repository->getLogList($server->server_id, $week, $week);
            $rows = array_reverse($rows);
            if (count($rows) < 2) return false;
            $up_count = 0;
            $fail_count = 0;
            foreach ($rows as $row) {
                if ($row->status >= 200 AND $row->status < 300) {
                    $up_count++;
                    if ($mode === 'down') return false;
                } else {
                    $fail_count++;
                    if ($mode === 'up') return false;
                }

                $this->debugMessage("mode: $mode, fail_count: $fail_count.");
                $this->debugMessage("mode: $mode, up_count: $up_count.");


                if ($mode === 'down' && $fail_count >= 2) return true;
                if ($mode === 'up' && $up_count >= 2) return true;
            }
        }
    }

}