<?php

declare(strict_types=1);

namespace PingMonitoringTool;

use DateTimeImmutable;
use DomainException;
use PDO;
use PDOException;
use PingMonitoringTool\Mailer\MailServer;
use stdClass;

class Repository
{
    const PATH_TO_SQLITE_FILE = ROOT . '/monitoring.db';
    private $pdo;

    public function __construct() {
        if ($this->pdo == null) {
            try {
                $opt = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                $this->pdo = new PDO("sqlite:" . self::PATH_TO_SQLITE_FILE, null, null, $opt);
            } catch (PDOException $e) {
                throw new DomainException($e->getMessage());
            }
        }
    }

    public function getTables(): array
    {
        $stm = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $data = $stm->fetchAll();
        return array_map(function ($value): string {
            return $value->name;
        }, $data);
    }

    public function getMonitoringList()
    {
        $stm = $this->pdo->query("SELECT * FROM `monitoring`");
        return $stm->fetchAll();
    }

    public function setDown(Domain $domain, ?Status $status): void
    {
        /**
         * @var $domainDB VerifiedDomain
         */
        $domainDB = $this->getDomain($domain);
        if (!is_null($domainDB)) {
            $domainDB->setSuccess(0);
            $domainDB->setLastAt(new DateTimeImmutable());
            $domainDB->increaseFalls();
            // Обновляем entity
            $this->setDomain($domainDB);
        }
    }
    public function setUp(Domain $domain, Status $status): void
    {
        /**
         * @var $domainDB VerifiedDomain
         */
        $domainDB = $this->getDomain($domain);
        if (!is_null($domainDB)) {
            $domainDB->setFalls(0);
            $domainDB->setLastAt(new DateTimeImmutable());
            $domainDB->increaseSuccess();
            // Обновляем entity
            $this->setDomain($domainDB);
        }
    }


    public function getDomain(Domain $domain): ?VerifiedDomain
    {
        $stm = $this->pdo->query("SELECT * FROM `monitoring` WHERE `domain`='{$domain->getValue()}'");
        if($res = $stm->fetch()) {
            $verifiedDomain = new VerifiedDomain($domain);
            $verifiedDomain->setSuccess((int) $res->success);
            $verifiedDomain->setFalls((int) $res->falls);
            try {
                $verifiedDomain->setLastAt(new DateTimeImmutable($res->last_at));
            } catch (\Exception $e) {
            }
            $verifiedDomain->setNotifyFalls((int) $res->notify_falls);
            $verifiedDomain->setNotifySuccess((int) $res->notify_success);
            $verifiedDomain->setWeek((int) $res->week);
            $verifiedDomain->setWeekReport((bool) $res->week_report);
            return $verifiedDomain;
        }
        return null;
    }

    private function setDomain(VerifiedDomain $domainDB): void
    {
        $sql = "UPDATE monitoring 
                SET `success`=:success, `falls`=:falls, `last_at`=:last_at, 
                    `notify_success`=:notify_success,`notify_falls`=:notify_falls,
                    `week`=:week, `week_report`=:week_report 
                WHERE `domain`=:domain";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':success', $domainDB->getSuccess());
        $stmt->bindValue(':falls', $domainDB->getFalls());
        $stmt->bindValue(':last_at', $domainDB->getLastAt()->format('Y-m-d H:i:s'));
        $stmt->bindValue(':notify_success',$domainDB->getNotifySuccess());
        $stmt->bindValue(':notify_falls',$domainDB->getNotifyFalls());
        $stmt->bindValue(':week', $domainDB->getWeek());
        $stmt->bindValue(':week_report', (int) $domainDB->isWeekReport());
        $stmt->bindValue(':domain', $domainDB->getDomain());
        $stmt->execute();
    }

    public function canSendNotify(Domain $domain): ?string
    {
        /**
         * @var $domainDB VerifiedDomain
         */
        $domainDB = $this->getDomain($domain);
        $failed_attempts = $this->getConfigParam('failed_attempts');
        $failed_attempts_count = 0;
        if(!empty($failed_attempts)) {
            $failed_attempts_count = (int)$failed_attempts[$domain->getValue()] ?? 2;
        }
        if (!is_null($domainDB)) {
            if ($domainDB->getSuccess() > 0) {
                $diff = $domainDB->getSuccess() - $domainDB->getFalls();
                if ($diff >= 2
                    && $domainDB->getNotifySuccess() <= 0
                    && ($domainDB->getNotifyFalls() < 0 || $domainDB->getNotifyFalls() > 0))
                {
                    return 'success';
                }
            } else {
                $diff = $domainDB->getFalls() - $domainDB->getSuccess();
                if ($diff >= $failed_attempts_count
                    && $domainDB->getNotifyFalls() <= 0
                    && ($domainDB->getNotifySuccess() < 0 || $domainDB->getNotifySuccess() > 0))
                {
                    return 'falls';
                }
            }
            return null;
        }
        return null;
    }

    public function setNotify(Domain $domain, string $typeSendNotify)
    {
        /**
         * @var $domainDB VerifiedDomain
         */
        $domainDB = $this->getDomain($domain);
        if (!is_null($domainDB)) {
            if ($typeSendNotify === 'success') {
                $domainDB->setNotifySuccess(1);
                $domainDB->setNotifyFalls(0);
            } else {
                $domainDB->setNotifySuccess(0);
                $domainDB->setNotifyFalls(1);
            }
            $this->setDomain($domainDB);
        }
    }

    /**
     * Получение настроек
     * @return string|array
     */
    public function getConfigParam($param)
    {
        $stm = $this->pdo->query("SELECT `value` FROM `config` WHERE `parameter`='$param'");
        $value = $stm->fetch()->value ?? '';
        if (is_array($value_array = json_decode($value, true))) {
            return $value_array;
        }
        return $value;
    }

    public function getMailerServers(): array
    {

        $nameServer = $this->getConfigParam('from_server');
        $from = $this->getConfigParam('from');
        $recipients = $this->getConfigParam('recipients');

        $base = new MailServer($nameServer);
        $base->setFrom($from);
        $base->setHost($this->getConfigParam('smtp_host_base'));
        $base->setPort('25');
        $base->setRecipients($recipients);


        $reserve = new MailServer($nameServer);
        $reserve->setFrom($from);
        $reserve->setHost($this->getConfigParam('smtp_host_reserve'));
        $reserve->setPort($this->getConfigParam('smtp_port_reserve'));
        $reserve->setUsername($this->getConfigParam('smtp_username_reserve'));
        $reserve->setPassword($this->getConfigParam('smtp_password_reserve'));
        $reserve->setRecipients($recipients);

        return [
            'base' => $base,
            'reserve' => $reserve
        ];
    }

    public function writeStats(Domain $domain, int $status)
    {
        $date = date('Y-m-d');
        $currRow = $this->getOrInsertStatIfNotExists($domain);

        $sql = "UPDATE stats 
                SET `success`=:success, `falls`=:falls
                WHERE `domain`=:domain AND `date`=:date";
        $stmt = $this->pdo->prepare($sql);

        if ($status) {
            $stmt->bindValue(':success', $currRow->success+1);
            $stmt->bindValue(':falls', $currRow->falls);
        } else {
            $stmt->bindValue(':success', $currRow->success);
            $stmt->bindValue(':falls', $currRow->falls+1);
        }

        $stmt->bindValue(':domain', $domain->getValue());
        $stmt->bindValue(':date', $date);
        $stmt->execute();
    }

    private function getStatRowForDate(Domain $domain, string $date)
    {
        $stm = $this->pdo->query("SELECT * FROM `stats` WHERE `domain`='{$domain->getValue()}' AND `date`='$date'");
        return $stm->fetch();
    }

    private function getOrInsertStatIfNotExists(Domain $domain)
    {
        $date = date('Y-m-d');
        $week = date('W');
        $currRowStat = $this->getStatRowForDate($domain, $date);

        if(!$currRowStat) {
            $sql = "INSERT INTO stats 
                (domain, date, week, success, falls) 
                VALUES('{$domain->getValue()}', '$date', '$week', '0', '0')";
            $this->pdo->exec($sql);
            $currRowStat = $this->getStatRowForDate($domain, $date);
        }

        return $currRowStat;
    }

    public function getWeekReport()
    {
        // Если записей с текущей недели отсутствуют, то делаем обновление
        $week = date("W");
        $stm = $this->pdo->query("SELECT * FROM `monitoring` WHERE `week`='$week'");
        if (!$stm->fetch()) {
            $this->pdo->exec("UPDATE `monitoring` SET `week`='$week', `week_report`='0'");
        }
        // Берем записи, по которым недельный отчет не отправлялся
        $stm = $this->pdo->query("SELECT * FROM `monitoring` WHERE `week`='$week' AND `week_report`='0'");
        $data = $stm->fetchAll();
        if (empty($data)) return false;
        // Обрабатываем записи, добавляя статистику за предыдущую неделю
        $week_ago = date("W", strtotime('monday previous week'));
        foreach ($data as $item) {
            $item->stats = $this->getStatForWeek($item->domain, $week_ago);
        }
        return $data;
    }

    private function getStatForWeek(string $domain, string $week_ago): stdClass
    {
        $stm = $this->pdo->query("SELECT * FROM `stats` 
                                           WHERE `week`='$week_ago' AND `domain`='$domain'");
        $data = $stm->fetchAll();
        $newdata = new stdClass();
        $all_success = 0;
        $all_falls = 0;
        foreach ($data as $item) {
            $all_success += $item->success;
            $all_falls += $item->falls;
        }
        $total_checks = $all_success + $all_falls;
        $uptime = 100;
        if ($total_checks > 0) {
            $uptime = round((100/$total_checks)*$all_success, 2);
        }


        $newdata->all_success = $all_success;
        $newdata->all_falls = $all_falls;
        $newdata->total_checks = $total_checks;
        $newdata->uptime = $uptime;
        $newdata->data = $data;
        $newdata->week = $week_ago;
        $newdata->domain = $domain;

        return $newdata;
    }

    public function setSendWeekReport()
    {
        $week = date("W");
        $this->pdo->exec("UPDATE `monitoring` SET `week_report`='1'");

    }

    public function canSendRepeatNotify(Domain $domain): bool
    {
        $repeat_down = (int) $this->getConfigParam('repeat_down');
        if (!$repeat_down) return false;

        $repeat_down_every_minutes = (int) $this->getConfigParam('repeat_down_every_minutes');

        $stm = $this->pdo->query("SELECT * FROM `monitoring` WHERE `domain`='{$domain->getValue()}' AND `notify_falls`='1'");
        if (!$record = $stm->fetch()) {
           return false;
        }

        if ($record->falls > 2 && $record->falls % $repeat_down_every_minutes === 0) {
            return true;
        }
        return false;
    }

    public function writeLog(string $type, string $message, string $domain = '', int $status = 0):void
    {
        $current_time = date("Y-m-d H:i:s");
        $sql = 'INSERT INTO logs(datetime, domain, type, status, message) 
                    VALUES(:datetime, :domain, :type, :status, :message)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':datetime', $current_time);
        $stmt->bindValue(':domain', $domain);
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':message', $message);
        $stmt->execute();
    }

}