<?php
namespace InteractivePlus\PDK2020Core\User;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use \InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\Utils\DataUtil;

class Token{
    public static function generateTokenValue(string $username) : string{
        return md5($username . rand(0,10000) . time() . Setting::getPDKSetting('TOKEN_SALT'));
    }
    public static function verifyToken(string $token) : bool{
        return strlen($token) === 32;
    }
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_token = NULL;
    private $_username = NULL;
    public $issueTime = 0;
    public $expireTime = 0;
    public $renewTime = 0;
    private $_client_addr = NULL;

    private $_createNewToken = false;

    //TODO: Finish this class

    public function readFromDataRow(array $dataRow) : void{
        $this->_token = $dataRow['token'];
        $this->_username = $dataRow['username'];
        $this->issueTime = $dataRow['issue_time'];
        $this->expireTime = $dataRow['expire_time'];
        $this->renewTime = $dataRow['renew_time'];
        $this->_client_addr = $dataRow['client_addr'];
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            'token' => $this->_token,
            'username' => $this->_username,
            'issue_time' => $this->issueTime,
            'expire_time' => $this->expireTime,
            'renew_time' => $this->renewTime,
            'client_addr' => $this->_client_addr
        );
        return $returnArr;
    }
    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in Token class');
        }
        
        $newDataArray = $this->saveToDataArray();
        $differenceArray = array();
        if(empty($this->_lastDataArray)){
            $differenceArray = $newDataArray;
        }else{
            $oldDataArray = $this->_lastDataArray;
            $differenceArray = DataUtil::compareDataArrayDifference($newDataArray,$oldDataArray);
        }
        $this->_Database->where('token',$this->_token);
        $updateRst = $this->_Database->update('logged_infos',$differenceArray);
        if(!$updateRst){
            throw new PDKException(
                50007,
                'Token update error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
        $this->_dataTime = time();
        $this->_lastDataArray = $newDataArray;
    }
    protected function insertToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in Token class');
        }
        $dataArray = $this->saveToDataArray();
        $insertedID = $this->_Database->insert('logged_infos',$dataArray);
        if(!$insertedID){
            throw new PDKException(
                50007,
                'Token insert error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
        $this->_dataTime = time();
        $this->_lastDataArray = $dataArray;
    }
    public function saveToDatabase() : void{
        if($this->_createNewToken){
            $this->insertToDatabase();
            $this->_createNewToken = false;
        }else{
            $this->updateToDatabase();
        }
    }
}