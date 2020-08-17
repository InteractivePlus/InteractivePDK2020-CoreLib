<?php
namespace InteractivePlus\PDK2020Core\Implementions\Storages;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Interfaces\Storages\LogStorage;
use InteractivePlus\PDK2020Core\Logs\LogEntity;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;
use MysqliDb;

class LogStorageMySQLImpl implements LogStorage{
    private $_Database;

    public function __construct(MysqliDb $database){
        $this->_Database = $database;
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    /**
     * @inheritdoc
     */
    public function addLog(
        LogEntity $entity
    ) : void{
        $insertData = array(
            'actionID' => $entity->actionID,
            'appuid' => $entity->getAPPUID(),
            'time' => $entity->time,
            'logLevel' => $entity->getLogLevel(),
            'message' => $entity->message,
            'success' => $entity->success ? 1 : 0,
            'PDKExceptionCode' => $entity->PDKExceptionCode,
            'context' => empty($entity->getContexts()) ? NULL : gzcompress(json_encode($entity->getContexts())),
            'clientAddr' => $entity->clientAddr
        );
        $insertState = $this->_Database->insert(
            'logs',
            $insertData
        );
        if(!$insertState){
            throw new PDKException(
                50007,
                __CLASS__ . ' insert error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function delLog(
        int $timeBefore,
        int $logLevelSmallerThan
    ) : void{
        $this->_Database->where('time',$timeBefore,'<=');
        $this->_Database->where('logLevel',$logLevelSmallerThan,'<=');

        $deleteStatus = $this->_Database->delete('logs');
        if(!$deleteStatus){
            throw new PDKException(
                50007,
                __CLASS__ . ' update error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
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
        $limitParam = NULL;
        if($limit !== -1 && $limit === 0){
            $limitParam = $limit;
        }else if($limit !== -1 && $offset !== 0){
            $limitParam = array($offset,$limit);
        }
        $resultArray = $this->_Database->withTotalCount()->get('logs',$limitParam);
        if($this->_Database->count <= 0){
            return new MultipleQueryResult($offset,0,$this->_Database->totalCount,NULL);
        }

        $dataTime = time();
        
        $logObjArr = array();
        foreach($resultArray as $singleRow){
            $logEntityObj = new LogEntity(
                $singleRow['actionID'],
                $singleRow['appuid'],
                $singleRow['time'],
                $singleRow['logLevel'],
                $singleRow['success'] === 1 ? true : false,
                $singleRow['PDKExceptionCode'],
                empty($singleRow['clientAddr']) ? NULL : $singleRow['clientAddr'],
                empty($singleRow['message']) ? NULL : $singleRow['message'],
                empty($singleRow['context']) ? array() : json_decode(gzuncompress($singleRow['context']),true)
            );
            $logObjArr[] = $logEntityObj;
        }
        return new MultipleQueryResult($offset,$this->_Database->count,$this->_Database->totalCount,$logObjArr);
    }

}