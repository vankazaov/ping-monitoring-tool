<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class Repository
{
    const PATH_TO_SQLITE_FILE = ROOT . '/monitoring.db';
    private $pdo;
    private $sapi = false;
    private $from_server;

    public function __construct() {
        if ($this->pdo == null) {
            try {
                $opt = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $this->pdo = new \PDO("sqlite:" . self::PATH_TO_SQLITE_FILE, null, null, $opt);
                $this->init();
            } catch (\PDOException $e) {
                throw new \DomainException($e->getMessage());
            }
        }
    }

    /**
     * Список серверов для мониторинга
     * @return array|false
     */
    public function getServers()
    {
        $stm = $this->pdo->query("SELECT * FROM `servers`");
        return $stm->fetchAll();
    }

    /**
     * Запись лога мониторинга
     * @param $server_id
     * @param $status
     * @param $message
     */
    public function writeLog($server_id, $status, $message)
    {
        $current_time = date("Y-m-d H:i:s");
        $sql = 'INSERT INTO logs(server_id, status, datetime, message) 
                    VALUES(:server_id, :status, :datetime, :message)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':server_id', $server_id);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':datetime', $current_time);
        $stmt->bindValue(':message', '['. $this->from_server . ']: ' . $message);
        $stmt->execute();
    }

    /**
     * Получение настроек
     * @return array|false
     */
    public function getConfig()
    {
        $stm = $this->pdo->query("SELECT * FROM `config`");
        return $stm->fetchAll();
    }
    /**
     * Получение настроек
     * @return array|false
     */
    public function getConfigParam($param)
    {
        $stm = $this->pdo->query("SELECT `value` FROM `config` WHERE `parameter`='$param'");
        return (int) $stm->fetch()->value;
    }

    /**
     * Удаление записей из таблицы мониторинга
     * @param $server_id
     */
    public function clearMonitoring($server_id) {
        $this->pdo->exec("DELETE FROM `monitoring` WHERE `server_id`='$server_id'");
    }

    /**
     * Добавление записи в мониторинг
     * @param $server_id
     * @param $status
     * @param $datetime
     * @param array $response
     * @param $notify
     */
    public function insertMonitoringServer($server_id, $status, $datetime, array $response, $notify)
    {
        $response_json = json_encode($response, JSON_UNESCAPED_UNICODE);
        $sql = 'INSERT INTO monitoring (server_id, status, datetime,response,notify) 
                VALUES(:server_id, :status, :datetime,:response, :notify)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':server_id', $server_id);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':datetime', $datetime);
        $stmt->bindValue(':response', $response_json);
        $stmt->bindValue(':notify', $notify);
        $stmt->execute();
    }

    /**
     * Проверяет, был ли сервер в нерабочем состоянии
     * @param $server_id
     * @return bool
     */
    public function isDown($server_id)
    {
        $stm = $this->pdo->query("SELECT * FROM `monitoring` WHERE `server_id`='$server_id' AND `status`='0'");
        if($stm->fetch()) return true;
        return false;
    }

    /**
     * Проверяет, был ли сервер в рабочем состоянии
     * @param $server_id
     * @return bool
     */
    public function isUp($server_id)
    {
        $stm = $this->pdo->query("SELECT * FROM `monitoring` WHERE `server_id`='$server_id' AND `status`='1'");
        if($stm->fetch()) return true;
        return false;
    }

    /**
     * Поиск сервера по имени
     * @param string $name_server
     * @return false
     */
    public function getServerIdByName(string $name_server)
    {
        $stm = $this->pdo->query("SELECT * FROM `servers` WHERE `server_name`='$name_server'");
        if($res = $stm->fetch()) return $res->server_id;
        return false;
    }

    /**
     * Получение списка записей мониторинга по которым не было отправки уведомлений
     * @return array|false
     */
    public function getNotNotifyMonitoring()
    {
        $stm = $this->pdo->query("SELECT * FROM monitoring WHERE notify='0'");
        return $stm->fetchAll();
    }

    /**
     * Получение списка записей мониторинга для повторной отправки уведомлений
     * @return array|false
     */
    public function getRepeatNotifyMonitoring()
    {
        $stm = $this->pdo->query("SELECT * FROM monitoring WHERE status='0' AND notify='1'");
        return $stm->fetchAll();
    }

    /**
     * Отметка в мониторинге об отпрвки уведомления
     * @param $server_id
     * @return bool
     */
    public function setSenderNotify($server_id)
    {
        $sql = "UPDATE monitoring "
            . "SET notify = 1 "
            . "WHERE server_id = :server_id";

        $stmt = $this->pdo->prepare($sql);

        // passing values to the parameters
        $stmt->bindValue(':server_id', $server_id);

        // execute the update statement
        return $stmt->execute();
    }
    /**
     * Снятие отметки в мониторинге об отпрвке уведомления
     * @param $server_id
     * @return bool
     */
    public function setNotSenderNotify($server_id)
    {
        $sql = "UPDATE monitoring "
            . "SET notify = 0 "
            . "WHERE server_id = :server_id";

        $stmt = $this->pdo->prepare($sql);

        // passing values to the parameters
        $stmt->bindValue(':server_id', $server_id);

        // execute the update statement
        return $stmt->execute();
    }

    /**
     * Получение информации о сервере по его id
     * @param $server_id
     * @return mixed
     */
    public function getServerById($server_id)
    {
        $stm = $this->pdo->query("SELECT * FROM `servers` WHERE  `server_id`='$server_id'");
        return $stm->fetch();
    }

    /**
     * Получение всех данных из таблицы (для дебага)
     * @param $table
     * @return array|false
     */
    public function getTableData($table)
    {
        $stm = $this->pdo->query("SELECT * FROM `$table` ");
        return $stm->fetchAll();
    }

    /**
     * Получает лог за текущий день (для вставки в письмо)
     * @param $server_id
     * @return string
     */
    public function getLog($server_id)
    {
        $date_current = date('Y-m-d 00:00:00');
        $stm = $this->pdo->query("SELECT * FROM `logs` 
            WHERE `server_id`='$server_id' AND `datetime` > '$date_current'");
        $message = '';
        foreach ($stm->fetchAll() as $item) {
            $message .= "{$item->datetime}: {$item->message}" . PHP_EOL;
        }
        return $message;
    }

    public function getLogList($server_id, $start_week, $end_week)
    {
        $stm = $this->pdo->query("SELECT * FROM `logs` 
            WHERE `server_id`='$server_id' AND `datetime` >= '$start_week 00:00:00' AND `datetime` <= '$end_week 23:59:59'");
        return $stm->fetchAll();
    }


    /**
     * Получает последнюю запись из лога об учпешной проверке сервера
     * @param $server_id
     * @return mixed
     */
    public function getLastLog($server_id)
    {
        $stm = $this->pdo->query("SELECT * FROM `logs` 
            WHERE `server_id`='$server_id' AND status > '200' AND status < '300' ORDER BY log_id DESC LIMIT 1");
        return $stm->fetch();
    }

    /**
     * Инициализация свойств класса данными из конфига
     */
    private function init()
    {
        foreach ($this->getConfig() as $object) {
            if (property_exists($this, $param = $object->parameter)) {
                if (is_array($value_array = json_decode($object->value, true))) {
                    $this->{$param} = $value_array;
                } else {
                    $this->{$param} = $object->value;
                }
            }
        }
    }

    public function getWeekReport($datetime)
    {
        $stm = $this->pdo->query("SELECT * FROM `reports` 
            WHERE `datetime`='$datetime' AND send = '1' LIMIT 1");
        return $stm->fetch();
    }

    public function calcUptime($start_week, $end_week)
    {
        $uptime_data = [];
        foreach ($this->getServers() as $server) {
            $logs = $this->getLogList($server->server_id, $start_week, $end_week);
            if (empty($logs)) continue;
            $uptime = [];
            $uptime['server_id'] = $server->server_id;
            $uptime['server_name'] = $server->server_name;
            $uptime['total_checks'] = count($logs);
            $uptime['successful_checks'] = 0;
            $uptime['failed_checks'] = 0;
            foreach ($logs as $log) {
                if ($log->status >= 200 && $log->status < 300) {
                    $uptime['successful_checks'] += 1;
                } else {
                    $uptime['failed_checks'] += 1;
                }
            }
            $uptime['uptime_percents'] = round((100/$uptime['total_checks'])*$uptime['successful_checks'], 2);
            $uptime_data[] = $uptime;
        }
        return $uptime_data;
    }

    public function setSenderReport($datereport)
    {
        $sql = 'INSERT INTO reports (datetime, send) VALUES(:datetime, :send)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':datetime', $datereport);
        $stmt->bindValue(':send', 1);
        $stmt->execute();
    }

}