<?php

declare(strict_types=1);

namespace PingMonitoringTool;

use RuntimeException;
use SQLite3;

class Setup
{
    private $db;

    private $sql_monitoring = "
        CREATE TABLE IF NOT EXISTS monitoring (
            domain TEXT PRIMARY KEY,
            success INTEGER NOT NULL,
            falls INTEGER NOT NULL,
            last_at TEXT NOT NULL,
            notify_success INTEGER NOT NULL,
            notify_falls INTEGER NOT NULL,
            week INTEGER NOT NULL,
            week_report INTEGER NOT NULL
        );
    ";
    private $sql_stats = "
        CREATE TABLE IF NOT EXISTS stats (
            domain TEXT NOT NULL,
            date TEXT NOT NULL,
            week INTEGER NOT NULL,
            success INTEGER NOT NULL,
            falls INTEGER NOT NULL
        );
    ";
    private $sql_stats_indx = "CREATE INDEX IF NOT EXISTS ind_col_stats ON stats (domain, date);";

    private $sql_config = "
        CREATE TABLE IF NOT EXISTS config (
            id INTEGER PRIMARY KEY,
            parameter TEXT NOT NULL,
            value TEXT NOT NULL
        );
    ";
    private $sql_logs = "
        CREATE TABLE IF NOT EXISTS logs (
            log_id INTEGER PRIMARY KEY,
            datetime TEXT NOT NULL,
            domain TEXT NOT NULL,
            type TEXT NOT NULL,
            status INTEGER NOT NULL,
            message TEXT NOT NULL
        );
    ";

    public function __construct()
    {
        if (!class_exists('SQLite3')) {
            throw new RuntimeException('SQLite3 extension not support!');
        }
        if (!file_exists(ROOT . '/config.ini')) {
            throw new RuntimeException('Missing configuration file' . ROOT .'/config.ini');
        }
    }

    public function init(): void
    {
        // Проверим, изменился ли файл конфигурации
        $md5 = md5_file(ROOT . '/config.ini');
        if (file_exists(ROOT . '/'.$md5.'.md5')) {
            return;
        }
        $this->db = new SQLite3(ROOT . '/monitoring.db');
        $this->db->exec($this->sql_monitoring);
        $this->db->exec($this->sql_stats);
        $this->db->exec($this->sql_stats_indx);
        $this->db->exec("DROP TABLE IF EXISTS config");
        $this->db->exec($this->sql_config);
        $this->db->exec($this->sql_logs);
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        foreach (glob(ROOT . "/*.md5") as $filename) {
            unlink($filename);
        }
        $ini_array = parse_ini_file(ROOT . "/config.ini");
        $this->removeOldDomainsFromMonitoring($ini_array['servers']);
        $this->loadDomains($ini_array['servers']);
        $this->loadParams($ini_array);
        $md5 = md5_file(ROOT . '/config.ini');
        file_put_contents(ROOT . "/$md5.md5", 'config.ini');
    }

    private function loadDomains(array $domains): void
    {
        foreach ($domains as $domain) {
            if (!$this->isExistDomain($domain)) {
                $date  = date('Y-m-d H:i:s');
                $week = date('W');
                $this->db->exec("INSERT INTO monitoring 
                (domain, success, falls, last_at, notify_success, notify_falls, week, week_report) 
                VALUES('$domain', '0', '0', '$date', '-1', '-1' , '$week', '0')");
            }
        }
    }

    private function isExistDomain($domain): bool
    {
        $res = $this->db->query("SELECT * FROM monitoring WHERE domain='$domain'");
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

    private function removeOldDomainsFromMonitoring($domains)
    {
        $exists_domain = $this->getExistsDomain();
        $for_delete = array_diff($exists_domain, $domains);
        foreach ($for_delete as $domain) {
            $this->deleteDomain($domain);
        }
    }

    private function getExistsDomain()
    {
        $exists_domains = [];
        $res = $this->db->query("SELECT * FROM monitoring");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $exists_domains[] = $row['domain'];
        }
        return $exists_domains;
    }

    private function deleteDomain($domain)
    {
        $this->db->exec("DELETE FROM monitoring WHERE domain='$domain'");
    }

}