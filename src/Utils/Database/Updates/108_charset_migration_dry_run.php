<?php

// created on 2024-11-17 by stefan1214 (stefopl)

namespace src\Utils\Database\Updates;

use PDO;
use src\Models\OcConfig\OcConfig;

class C17312809970923 extends UpdateScript
{
    const DRY_RUN = false;
    const CHARSET_TARGET = 'utf8mb4';
    const COLLATION_TARGET = 'utf8mb4_general_ci';
    private $databaseName;

    public function setDatabaseName($databaseName): void
    {
        $this->databaseName = $databaseName;
    }

    public function getProperties()
    {
        return [
            // see /docs/DbUpdate.md
            'uuid' => '6717B3F0-03F8-D304-B0B4-37395E56419E',
            'run' => 'auto',
        ];
    }

    // IMPORTANT:
    // Any output by 'echo', 'print' etc. will be PUBLIC (see #1923).
    // Do not output any sensitive information.

    public function run()
    {
        // Insert your update code here, using $this->db for database access.

        // The update will be run inside a transaction. It will also run
        // with set_time_limit(0), so don't create any endless loops!

        $this->db->beginTransaction();

        try {
            if (!$this->databaseName) {
                $conf = OcConfig::instance();
                $this->setDatabaseName($conf->getDbName());
            }

            $this->log("Starting charset migration for database: `{$this->databaseName}`");
            $this->log('');

            //Query: ALTER TABLE `search_words` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
            //SQLSTATE[42000]: Syntax error or access violation: 1071 Specified key was too long; max key length is 1000 bytes
            $this->queryAndLog('DROP INDEX `hash` ON `search_words`');
            $this->queryAndLog('CREATE INDEX `hash` ON `search_words` (`hash`, `word`(191));');

            $this->applyTablesChnages();
            $this->log('');
            $this->log('');
            $this->applyColumnChanges();
            $this->log('');
            $this->log('');
            $this->log('Charset migration completed.');
        } catch (Exception $e) {
            $this->db->rollback();

            $this->log('Error occurred during charset migration: ' . $e->getMessage());

            throw $e;
        }
    }

    private function applyTablesChnages()
    {
        $charsetTarget = self::CHARSET_TARGET;
        $collationTarget = self::COLLATION_TARGET;

        $stmt = $this->db->prepare("
            SELECT TABLE_NAME, TABLE_COLLATION
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = :databaseName
              AND TABLE_NAME NOT LIKE 'okapi_%'
        ");
        $stmt->execute(['databaseName' => $this->databaseName]);
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tables as $table) {
            $this->log('');
            $tableName = $table['TABLE_NAME'];
            $currentCollation = $table['TABLE_COLLATION'];

            if (!$currentCollation) {
                $this->log("Table `{$tableName}` has no collation set. Skipping.");

                continue;
            }

            if ($currentCollation !== $collationTarget) {
                $query = "ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET {$charsetTarget} COLLATE {$collationTarget}";
                $this->log("[PENDING] `{$tableName}` Before: {$currentCollation}");
                $this->log("[PENDING] `{$tableName}` After : {$collationTarget}");
                $before = $this->tableDetails($tableName, '[Before] ');
                $this->queryAndLog($query);
                if (!self::DRY_RUN) {
                    $after = $this->tableDetails($tableName, '[After ] ');
                }

                if (!empty($before)) {
                    $this->log($before);
                }
                if (!self::DRY_RUN) {
                    if (!empty($after)) {
                        $this->log($after);
                    }
                }
            } else {
                $this->log("Table `{$tableName}` is already using charset `{$charsetTarget}`. Skipping.");
            }
        }
    }

    private function applyColumnChanges()
    {
        $columnChanges = [
            "ALTER TABLE `PowerTrail` CHANGE `image` `image` TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT ''",
            "ALTER TABLE `gk_item` CHANGE `description` `description` LONGTEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT ''",
            "ALTER TABLE `gk_item` CHANGE `userid` `userid` INT(11) NOT NULL DEFAULT '0'",
            "ALTER TABLE `gk_item` CHANGE `datecreated` `datecreated` DATETIME NULL DEFAULT NULL",
            "ALTER TABLE `gk_item` CHANGE `datemodified` `datemodified` DATETIME NULL DEFAULT NULL",
            "ALTER TABLE `gk_item` CHANGE `typeid` `typeid` INT(11) NOT NULL DEFAULT '0'",
            "ALTER TABLE `gk_item` CHANGE `stateid` `stateid` TINYINT(4) NOT NULL DEFAULT '0'",
            "ALTER TABLE `PowerTrail_cacheCandidate` CHANGE `link` `link` TEXT CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT ''",
        ];

        $this->log('Applying specific column changes:');

        foreach ($columnChanges as $query) {
            if (preg_match('/ALTER TABLE `(.*?)`.*?CHANGE `(.*?)`/', $query, $matches)) {
                $tableName = $matches[1];
                $columnName = $matches[2];

                // Get column details before the change
                $before = $this->getColumnDetails($tableName, $columnName);

                // Apply the ALTER TABLE query
                $this->queryAndLog($query);

                // Get column details after the change
                $after = $this->getColumnDetails($tableName, $columnName);

                // Log the changes between before and after states
                $this->logFieldChange($tableName, $columnName, $before, $after);
            } else {
                // In case the ALTER query does not match the expected format
                $this->queryAndLog($query);
            }
        }
    }

    private function getColumnDetails($tableName, $columnName)
    {
        try {
            $query = '
            SELECT 
                COLUMN_NAME, 
                COLUMN_TYPE, 
                IS_NULLABLE, 
                COLUMN_DEFAULT, 
                CHARACTER_SET_NAME, 
                COLLATION_NAME, 
                COLUMN_COMMENT
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = :tableName 
                AND COLUMN_NAME = :columnName
        ';

            $stmt = $this->db->prepare($query);
            $stmt->execute(['tableName' => $tableName, 'columnName' => $columnName]);

            if ($stmt->rowCount() == 0) {
                throw new Exception("Column `{$columnName}` does not exist in table `{$tableName}`.");
            }

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return;
        }
    }

    private function logFieldChange($tableName, $columnName, $before, $after)
    {
        $changes = [];

        foreach ($before as $key => $value) {
            if ($before[$key] !== $after[$key]) {
                $changes[] = "Changed {$key}: `{$value}` -> `{$after[$key]}`";
            }
        }

        if (count($changes) > 0) {
            $this->log("Column `{$columnName}` in table `{$tableName}` changes:");

            foreach ($changes as $change) {
                $this->log($change);
            }
        } else {
            $this->log("No changes for column `{$columnName}` in table `{$tableName}`.");
        }
    }

    private function log($message)
    {
        if (!empty($message)) {
            echo (self::DRY_RUN ? '[DRY RUN] ' : '') . "{$message}\n";
            error_log((self::DRY_RUN ? '[DRY RUN] ' : '') . "{$message}");
        } else {
            echo "\n";

        }
    }

    private function queryAndLog($query)
    {
        $startTime = microtime(true);

        if (!self::DRY_RUN) {
            $this->db->simpleQuery($query);
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $timeLog = sprintf('[TIME: %.4f seconds]', $executionTime);
        echo (self::DRY_RUN ? '[DRY RUN QUERY] ' : '[QUERY] ') . "{$query} {$timeLog}\n";
        error_log((self::DRY_RUN ? '[DRY RUN QUERY] ' : '[QUERY] ') . "{$query} {$timeLog}");
    }

    public function tableDetails($tableName, $prefix)
    {
        try {
            $query = '
            SELECT TABLE_NAME, TABLE_COLLATION, ENGINE
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = :tableName
        ';

            $stmt = $this->db->prepare($query);
            $stmt->execute(['tableName' => $tableName]);

            if ($stmt->rowCount() == 0) {
                throw new Exception("Brak danych dla tabeli `{$tableName}`. Sprawdź, czy masz odpowiednie uprawnienia.");
            }

            $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            return $prefix . $tableInfo['TABLE_NAME'] . ', ' . $tableInfo['TABLE_COLLATION'] . ', ' . $tableInfo['ENGINE'];
        } catch (Exception $e) {
            return;
        }
    }

    public function rollback()
    {
        // If possible and feasible, provide code here which reverses the
        // changes made by run(). Otherwiese please REMOVE the rollback method.
        // This will disable the "rollback" action on the Admin.DbUpdate page.

        // The rollback will be run inside a transaction. It will also run
        // with set_time_limit(0), so don't create any endless loops!

        $this->log('Rollback is not implemented for charset migrations.');
    }
};

return new C17312809970923;
