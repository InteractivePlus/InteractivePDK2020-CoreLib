<?php
namespace InteractivePlus\PDK2020Core\Exceptions;
class PDKException extends \Exception{
    private $err_params = null;
    public function __construct(int $code, string $message, array $errParams = null, \Exception $previous = null){
        $this->err_params = $errParams;
        parent::__construct($message,$code,null,$previous);
    }
    public function getErrorParams(){
        return $this->err_params;
    }
    public function __toString(){
        $response_Array = array(
            'errorCode' => $this->getCode(),
            'errorDescription' => $this->getMessage(),
            'errorParams' => $this->getErrorParams(),
            'errorFile' => $this->getFile(),
            'errorLine' => $this->getLine()
        );
        return json_encode($response_Array);
    }
}