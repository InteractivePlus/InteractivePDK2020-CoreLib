<?php
namespace InteractivePlus\PDK2020Core\Interfaces\Storages;

use InteractivePlus\PDK2020Core\Logs\LogEntity;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;

interface LogStorage{
    /**
     * Adds a log record to storage
     * @param entity log entity
     */
    public function addLog(
        LogEntity $entity
    ) : void;

    /**
     * delete logs stored in storage
     * @param timeBefore only delete logs logged before this UNIX time stamp.
     * @param logLevelSmallerThan only delete logs that has log levels lower than this number.
     */
    public function delLog(
        int $timeBefore,
        int $logLevelSmallerThan
    ) : void;

    /**
     * search log items
     */
    public function searchLogs(
        int $timeFrom,
        int $timeTo,
        int $logLevelHigherThan,
        int $limit = -1,
        int $offset = 0
    ) : MultipleQueryResult;
}