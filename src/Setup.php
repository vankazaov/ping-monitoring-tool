<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class Setup
{

    private $sql_servers = "
        CREATE TABLE IF NOT EXISTS servers (
            server_id INTEGER PRIMARY KEY,
            server_name TEXT NOT NULL UNIQUE
        );
    ";
    private $sql_logs = "
        CREATE TABLE IF NOT EXISTS logs (
            log_id INTEGER PRIMARY KEY,
            server_id INTEGER NOT NULL,
            status INTEGER NOT NULL,
            datetime TEXT NOT NULL,
            message TEXT NOT NULL
        );
    ";
    private $sql_monitoring = "
        CREATE TABLE IF NOT EXISTS monitoring (
            server_id INTEGER,
            status INTEGER NOT NULL,
            datetime TEXT NOT NULL,
            response TEXT NOT NULL,
            notify INTEGER NOT NULL
        );
    ";
    private $sql_report = "
        CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY,
            datetime TEXT NOT NULL,
            send INTEGER NOT NULL
        );
    ";
    private $sql_config = "
        CREATE TABLE IF NOT EXISTS config (
            id INTEGER PRIMARY KEY,
            parameter TEXT NOT NULL,
            value TEXT NOT NULL
        );
    ";

    private $db;
    public static $ready = false;


    public function __construct()
    {
        self::$ready = false;
        if (!file_exists(ROOT . '/config.ini')) {
            throw new \DomainException('Missing configuration file' . ROOT .'/config.ini');
        } else {
            $md5 = md5_file(ROOT . '/config.ini');
            if (file_exists(ROOT . '/'.$md5.'.md5')) {
                self::$ready = true;
                return;
            }
        }

        $this->db = new \SQLite3(ROOT . '/monitoring.db');
        $this->db->exec($this->sql_servers);
        $this->db->exec($this->sql_logs);
        $this->db->exec($this->sql_monitoring);
        $this->db->exec($this->sql_report);
        $this->db->exec("DROP TABLE IF EXISTS config");
        $this->db->exec($this->sql_config);
        $this->loadConfig();
        self::$ready = true;
    }

    private function loadConfig()
    {
        foreach (glob(ROOT . "/*.md5") as $filename) {
            unlink($filename);
        }
        $ini_array = parse_ini_file(ROOT . "/config.ini");
        $this->loadServers($ini_array['servers']);
        $this->loadParams($ini_array);
        $md5 = md5_file(ROOT . '/config.ini');
        file_put_contents(ROOT . "/$md5.md5", 'config.ini');
        $this->insertStartLog();
    }

    private function loadServers(array $servers)
    {
        foreach ($servers as $server) {
            if (!$this->isExistServer($server)) {
                $this->db->exec("INSERT INTO servers (server_name) VALUES('$server')");
            }
        }
    }

    private function insertStartLog()
    {
        $results = $this->db->query('SELECT * FROM servers');
        $date_current = date("Y-m-d H:i:s");
        while ($row = $results->fetchArray()) {
            if (!$this->isExistStartLog($row['server_id'])) {
                $this->db->exec("INSERT INTO logs (server_id, status, datetime, message) 
            VALUES('{$row['server_id']}', '201', '$date_current', 'Start log for {$row['server_name']}')");
            }
        }
    }

    private function isExistStartLog($server_id)
    {
        $res = $this->db->query("SELECT * FROM logs WHERE server_id='$server_id' AND status='201'");
        return is_array($res->fetchArray());
    }

    private function isExistServer($server)
    {
        $res = $this->db->query("SELECT * FROM servers WHERE server_name='$server'");
        return is_array($res->fetchArray());
    }

    private function loadParams($params)
    {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            $this->db->exec("INSERT INTO config (parameter, value) VALUES('$key', '$value')");
        }
    }
}