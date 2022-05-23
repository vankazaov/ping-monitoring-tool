<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class PrintDb
{
    const PATH_TO_SQLITE_FILE = ROOT . '/monitoring.db';
    private $pdo;

    public function __construct() {
        if ($this->pdo == null) {
            try {
                $opt = [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $this->pdo = new \PDO("sqlite:" . self::PATH_TO_SQLITE_FILE, null, null, $opt);
            } catch (\PDOException $e) {
                throw new \DomainException($e->getMessage());
            }
        }
    }

    private function getTables(): array
    {
        $stm = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $data = $stm->fetchAll();
        return array_map(fn($value): string => $value->name, $data);
    }

    public function printConsole(string $onlyTable = '')
    {
        foreach ($this->getTables() as $table) {
            if (isset($onlyTable) &&$onlyTable !== $table) continue;
            echo "TABLE: $table" . PHP_EOL;
            foreach ($this->getData($table) as $key => $row)
            {
                echo "Row #$key" . PHP_EOL;
                var_dump($row);
            }

        }
    }

    private function getData(string $table)
    {
        $stm = $this->pdo->query("SELECT * FROM $table");
        return $stm->fetchAll();
    }
}