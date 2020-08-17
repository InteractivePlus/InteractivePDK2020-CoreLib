<?php
namespace InteractivePlus\PDK2020Core\Logs;

use InteractivePlus\PDK2020Core\Apps\AppEntity;

class LogEntity{
    public $actionID = 0;
    private $_appuid = 0;
    public $time = 0;
    private $_logLevel = LogLevel::INFO;
    public $message = NULL;
    public $success = false;
    public $PDKExceptionCode = 0;
    private $_context = array();
    public $clientAddr = NULL;
    public function __construct(
        int $actionID, 
        int $appUID, 
        int $time, 
        int $logLevel, 
        bool $success,
        int $PDKExceptionCode = 0,
        ?string $clientAddr = NULL,
        ?string $message = NULL, 
        ?array $context = NULL
    ){
        if($context === NULL){
            $context = array();
        }
        $this->actionID = $actionID;
        $this->_appuid = $appUID;
        $this->time = $time;
        $this->_logLevel = LogLevel::fixLogLevel($logLevel);
        $this->success = $success;
        $this->PDKExceptionCode = $PDKExceptionCode;
        $this->clientAddr = $clientAddr;
        $this->message = $message;
        $this->_context = $context;
    }
    public function getAPPUID() : int{
        return $this->_appuid;
    }
    public function setAPP(AppEntity $app) : void{
        $this->_appuid = $app->getAppUID();
    }
    public function getLogLevel() : int{
        return $this->_logLevel;
    }
    public function setLogLevel(int $logLevel) : void{
        $this->_logLevel = LogLevel::fixLogLevel($logLevel);
    }
    public function getContexts() : array{
        return $this->_context;
    }
    public function getContext(string $key){
        return $this->_context[$key];
    }
    public function setContext(string $key, $val) : void{
        if($val === NULL){
            unset($this->_context);
        }else{
            $this->_context[$key] = $val;
        }
    }
    public function delContext(string $key) : void{
        $this->setContext($key,NULL);
    }
}