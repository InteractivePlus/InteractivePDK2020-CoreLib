<?php
namespace InteractivePlus\PDK2020Core\Logs;

use Psr\Log\AbstractLogger;
use InteractivePlus\PDK2020Core\Interfaces\Storages\LogStorage;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;

class LogRepository extends AbstractLogger implements LogStorage{
    private $_logStorage;
    public function __construct(LogStorage $logStorage)
    {
        $this->_logStorage = $logStorage;
    }
    public function getLogStorage() : LogStorage{
        return $this->_logStorage;
    }
    /**
     * @inheritdoc
     */
    public function log($level, $message, array $context = array()){
        $this->_logStorage->addLog(
            new LogEntity(
                90001,
                0,
                time(),
                LogLevel::fromPSRLogLevel($level),
                false,
                0,
                NULL,
                $message,
                $context
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function addLog(
        LogEntity $entity
    ) : void{
        $this->_logStorage->addLog($entity);
    }

    public function addLogItem(
        int $actionID, 
        int $appUID, 
        int $logLevel, 
        bool $success,
        int $PDKExceptionCode = 0,
        ?string $clientAddr = NULL,
        ?string $message = NULL, 
        ?array $context = NULL
    ){
        $this->_logStorage->addLog(
            new LogEntity(
                $actionID,
                $appUID,
                time(),
                $logLevel,
                $success,
                $PDKExceptionCode,
                $clientAddr,
                $message,
                $context
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function delLog(
        int $timeBefore,
        int $logLevelSmallerThan
    ) : void{
        $this->_logStorage->delLog($timeBefore,$logLevelSmallerThan);
    }

    /**
     * @inheritdoc
     */
    public function searchLogs(
        int $timeFrom,
        int $timeTo,
        int $logLevelHigherThan,
        int $limit = -1,
        int $offset = 0
    ) : MultipleQueryResult{
        return $this->_logStorage->searchLogs(
            $timeFrom,
            $timeTo,
            $logLevelHigherThan,
            $limit,
            $offset
        );
    }
}