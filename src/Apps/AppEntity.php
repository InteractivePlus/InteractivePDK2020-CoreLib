<?php
namespace InteractivePlus\PDK2020Core\Apps;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Formats\APPFormat;
use InteractivePlus\PDK2020Core\Formats\UserFormat;
use InteractivePlus\PDK2020Core\Utils\IntlUtil;
use MysqliDb;

class AppEntity{
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_appuid = 0;
    private $_display_name = NULL;
    private $_client_id = NULL;
    private $_client_secret = NULL;
    private $_client_type = AppClientType::PRIVATE_CLIENT;
    private $_developer_type = AppDeveloperType::THIRD_PARTY;
    private $_reg_area = NULL;
    public $reg_time = 0;
    public $avatar_md5 = NULL;
    private $_manage_list = array();

    private $_createNewAppEntity = false;

    private function __construct(){
        
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getAppUID() : int{
        return $this->_appuid;
    }

    public function getDisplayName() : string{
        return $this->_display_name;
    }

    public function setDisplayName(string $displayName) : void{
        if($this->_display_name === $displayName){
            return;
        }
        if(!UserFormat::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display name format incorrect',array('credential'=>'display_name'));
            return;
        }
        if(self::checkDisplaynameExist($this->_Database,$displayName)){
            throw new PDKException(20005,'Display name already exist');
        }
        $this->_display_name = $displayName;
    }

    public function getClientID() : string{
        return $this->_client_id;
    }

    public function setClientID(string $clientID) : void{
        if(!APPFormat::checkClientID($clientID)){
            throw new PDKException(30002,'ClientID format incorrect',array('credential'=>'client_id'));
        }
        if(self::checkClientIDExist($this->_Database, $clientID)){
            throw new PDKException(20004,'Client ID already exist');
        }
        $this->_client_id = $clientID;
    }

    public function getClientSecret() : string{
        return $this->_client_secret;
    }

    public function checkClientSecret(string $clientSecret) : bool{
        if(strtoupper($clientSecret) === strtoupper($this->getClientSecret())){
            return true;
        }else{
            return false;
        }
    }

    public function setClientSecret(string $clientSecret) : void{
        if(!APPFormat::checkClientSecret($clientSecret)){
            throw new PDKException(30002,'Client Secret format incorrect',array('credential'=>'client_secret'));
        }
        $this->_client_secret = $clientSecret;
    }

    public function getClientType() : int{
        return $this->_client_type;
    }

    public function setClientType(int $type) : void{
        $this->_client_type = AppClientType::fixClientType($type);
    }

    public function getDeveloperType() : int{
        return $this->_developer_type;
    }

    public function setDeveloperType(int $type) : void{
        $this->_developer_type = AppDeveloperType::fixDeveloperType($type);
    }

    public function getRegistrationArea() : string{
        return $this->_reg_area;
    }

    public function setRegistrationArea(string $area) : void{
        $this->_reg_area = IntlUtil::fixArea($area);
    }
    

    public function readFromDataRow(array $dataRow) : void{
        $this->_token = $dataRow['token'];
        $this->_uid = $dataRow['uid'];
        $this->issueTime = $dataRow['issue_time'];
        $this->expireTime = $dataRow['expire_time'];
        $this->renewTime = $dataRow['renew_time'];
        $this->_client_addr = $dataRow['client_addr'];
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            'token' => $this->_token,
            'uid' => $this->_uid,
            'issue_time' => $this->issueTime,
            'expire_time' => $this->expireTime,
            'renew_time' => $this->renewTime,
            'client_addr' => $this->_client_addr
        );
        return $returnArr;
    }
    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . ' class');
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
                __CLASS__ . ' update error',
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
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . ' class');
        }
        $dataArray = $this->saveToDataArray();
        $insertedID = $this->_Database->insert('logged_infos',$dataArray);
        if(!$insertedID){
            throw new PDKException(
                50007,
                __CLASS__ . ' insert error',
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

    public function delete() : void{
        if($this->_createNewToken){
            $this->_createNewToken = false;
            return;
        }
        //Delete existing record row of this token
        {
            $this->_Database->where('token',$this->_token);
            $updateRst = $this->_Database->delete('logged_infos');
            if(!$updateRst){
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
        //successfully deleted, now need to unset the Token variable
    }
    
    public static function createToken(
        MysqliDb $Database,
        User $user,
        string $client_ip,
        string $customTokenID = NULL
    ) : Token{
        $actualToken = '';
        if(!empty($customTokenID)){
            if(self::verifyToken($customTokenID)){
                $actualToken = $customTokenID;
            }else{
                throw new PDKException(30002,'Token format incorrect',array('credential'=>'token'));
            }
        }else{
            $actualToken = self::generateTokenValue($user->getUsername());
        }
        
        //check replication of tokens first
        if(self::checkTokenIDExist($Database,$actualToken)){
            if(!empty($customTokenID)){
                throw new PDKException(70003, 'Token already exist');
            }
            //regenerate actual token and return the new token.
            return self::createToken($Database,$user,$client_ip,$customTokenID);
        }

        $returnObj = new Token();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_uid = $user->getUID();
        $returnObj->_token = $actualToken;
        $returnObj->_client_addr = $client_ip;
        $returnObj->issueTime = $ctime;
        $returnObj->renewTime = $ctime;
        $returnObj->expireTime = $ctime + Setting::TOKEN_AVAILABLE_DURATION;

        $returnObj->_createNewToken = true;
        return $returnObj;
    }

    public static function fromTokenID(MysqliDb $Database, string $token){
        if(!self::verifyToken($token)){
            throw new PDKException(30002,'Token format incorrrect',array('credential'=>'token'));
        }
        $Database->where('token',$token);
        $dataRow = $Database->getOne('logged_infos');
        if(!$dataRow){
            throw new PDKException(70002,'Token non-existant');
        }
        $returnObj = new Token();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewToken = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function checkClientIDExist(MysqliDb $Database, string $clientID) : bool{
        $Database->where('client_id',$clientID);
        $count = $Database->getValue('logged_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkDisplaynameExist(MysqliDb $Database, string $displayName) : bool{
        $Database->where('display_name',$displayName);
        $count = $Database->getValue('app_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function clearTokenID(MysqliDb $Database,int $expireEarlierThan){
        $Database->where('expire_time',$expireEarlierThan,'<');
        $updateRst = $Database->delete('logged_infos');
        if(!$updateRst){
            throw new PDKException(
                50007,
                __CLASS__ . ' update error',
                array(
                    'errNo'=>$Database->getLastErrno(),
                    'errMsg'=>$Database->getLastError()
                )
            );
        }
    }
}