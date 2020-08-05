<?php
namespace InteractivePlus\PDK2020Core\Apps;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use MysqliDb;

class APPManagementRelations{
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_appuid = 0;
    private $_owner = NULL;
    private $_otherPermissionList = array();

    private $_createNewRelation = false;

    private function __construct(){
        
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function readFromDataRows(array $dataRows) : void{
        //reset variables
        $this->_owner = NULL;
        $this->_otherPermissionList = array();

        foreach($dataRows as $singleRow){
            switch($singleRow['role']){
                case APPManagementPermissionTypes::OWNER:
                    if($this->_owner !== NULL){
                        throw new PDKException(51000,'Two or more owners for one app');
                    }
                    $this->_owner = $singleRow['uid'];
                break;
                default:
                    $this->_otherPermissionList[] = new APPManagementRelation($singleRow['uid'],$singleRow['role']);
            }
        }
        if(!empty($dataRows)){
            $this->_appuid = $dataRows[0]['appuid'];
        }else{
            $this->_appuid = -1;
        }
    }
    public function saveToDataArray() : array{
        $returnArr = array();

        //Add Owner
        $returnArr[] = array(
            'appuid' => $this->_appuid,
            'uid' => $this->_owner,
            'role' => APPManagementPermissionTypes::OWNER
        );

        //Add other list members
        foreach($this->_otherPermissionList as $permItem){
            $returnArr[] = array(
                'appuid' => $this->_appuid,
                'uid' => $permItem->userUID,
                'role' => $permItem->getRole()
            );
        }

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
        $this->_Database->where('appuid',$this->_appuid);
        $updateRst = $this->_Database->update('app_infos',$differenceArray);
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
        $insertedID = $this->_Database->insert('app_infos',$dataArray);
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

        //Update New UID
        $newUID = $this->getDatabase()->getValue('app_infos','last_insert_id()');
        $this->_appuid = $newUID;
    }
    public function saveToDatabase() : void{
        if($this->_createNewAppEntity){
            $this->insertToDatabase();
            $this->_createNewAppEntity = false;
        }else{
            $this->updateToDatabase();
        }
    }
    
    public static function createAppEntity(
        MysqliDb $Database,
        User $owner,
        string $displayName,
        string $clientIDOverride = NULL,
        string $clientSecretOverride = NULL,
        int $developerType = AppDeveloperType::THIRD_PARTY,
        int $clientType = AppClientType::PRIVATE_CLIENT,
        string $regArea = Setting::DEFAULT_COUNTRY
    ) : AppEntity{
        $actualClientID = '';
        if(!empty($clientIDOverride)){
            if(APPFormat::checkClientID($clientIDOverride)){
                $actualClientID = $clientIDOverride;
            }else{
                throw new PDKException(30002,'ClientID format incorrect',array('credential'=>'client_id'));
            }
        }else{
            $actualClientID = APPFormat::generateClientID();
        }

        $actualClientSecret = '';
        if(!empty($clientSecretOverride)){
            if(APPFormat::checkClientSecret($clientSecretOverride)){
                $actualClientSecret = $clientSecretOverride;
            }else{
                throw new PDKException(30002,'Client Secret format incorrect',array('credential'=>'client_secret'));
            }
        }else{
            $actualClientSecret = APPFormat::generateClientSecret();
        }

        if(!UserFormat::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display Name format incorrect',array('credential'=>'display_name'));
        }
        
        //check replication of clientID first
        if(self::checkClientIDExist($Database,$actualClientID)){
            if(!empty($customTokenID)){
                throw new PDKException(20004, 'ClientID already exist');
                return;
            }
            //regenerate clientID and return the new APPEntity.
            return self::createAppEntity(
                $Database,
                $owner,
                $displayName,
                NULL,
                $clientSecretOverride,
                $developerType,
                $clientType,
                $regArea
            );
        }

        $returnObj = new AppEntity();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_appuid = -1;
        $returnObj->_display_name = $displayName;
        $returnObj->_client_id = $actualClientID;
        $returnObj->_client_secret = $actualClientSecret;
        $returnObj->_client_type = $clientType;
        $returnObj->_developer_type = $developerType;
        $returnObj->_reg_area = $regArea;
        $returnObj->reg_time = time();
        $returnObj->avatar_md5 = NULL;

        $returnObj->_createNewAppEntity = true;
        return $returnObj;
    }

    public static function fromClientID(MysqliDb $Database, string $clientID){
        if(!APPFormat::checkClientID($clientID)){
            throw new PDKException(30002,'ClientID format incorrrect',array('credential'=>'client_id'));
        }
        $Database->where('client_id',$clientID);
        $dataRow = $Database->getOne('app_infos');
        if(!$dataRow){
            throw new PDKException(20001,'APP non-existant');
        }
        $returnObj = new AppEntity();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewAppEntity = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function fromAPPUID(MysqliDb $Database, int $appUID){
        $Database->where('appuid',$appUID);
        $dataRow = $Database->getOne('app_infos');
        if(!$dataRow){
            throw new PDKException(20001,'APP non-existant');
        }
        $returnObj = new AppEntity();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewAppEntity = false;
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
}