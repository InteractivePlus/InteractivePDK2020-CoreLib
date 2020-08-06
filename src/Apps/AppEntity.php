<?php
namespace InteractivePlus\PDK2020Core\Apps;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Formats\APPFormat;
use InteractivePlus\PDK2020Core\Formats\UserFormat;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\User\User;
use InteractivePlus\PDK2020Core\Utils\DataUtil;
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
    private $_redirect_uri = NULL;

    protected $_managementRelations = NULL;

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
        $this->_client_id = strtolower($clientID);
    }

    public function getClientSecret() : string{
        return $this->_client_secret;
    }

    public function checkClientSecret(string $clientSecret) : bool{
        if(strtolower($clientSecret) === strtolower($this->getClientSecret())){
            return true;
        }else{
            return false;
        }
    }

    public function setClientSecret(string $clientSecret) : void{
        if(!APPFormat::checkClientSecret($clientSecret)){
            throw new PDKException(30002,'Client Secret format incorrect',array('credential'=>'client_secret'));
        }
        $this->_client_secret = strtolower($clientSecret);
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

    public function getRedirectURI() : string{
        return $this->_redirect_uri;
    }

    public function setRedirectURI(string $URI) : void{
        if(!empty($URI) && strlen($URI) > 500){
            throw new PDKException(30002,'URI cannot exceed 500 characters',array('credential'=>'redirect_uri'));
        }
        $this->_redirect_uri = $URI;
    }

    public function getManageRelations() : APPManagementRelations{
        return $this->_managementRelations;
    }

    public function readFromDataRow(array $dataRow) : void{
        $this->_appuid = $dataRow['appuid'];
        $this->_display_name = empty($dataRow['display_name']) ? NULL : $dataRow['display_name'];
        $this->_client_id = $dataRow['client_id'];
        $this->_client_secret = empty($dataRow['client_secret']) ? NULL : $dataRow['client_secret'];
        
        $developerTypeReceiver = 0;
        $clientTypeReceiver = 0;
        APPFormat::decodeClientType($dataRow['client_type'],$developerTypeReceiver,$clientTypeReceiver);
        $this->_developer_type = $developerTypeReceiver;
        $this->_client_type = $clientTypeReceiver;
        $this->_reg_area = empty($dataRow['reg_area']) ? NULL : $dataRow['reg_area'];
        $this->avatar_md5 = empty($dataRow['avatar']) ? NULL : $dataRow['avatar'];
        $this->redirect_uri = empty($dataRow['redirect_uri']) ? NULL : $dataRow['redirect_uri'];
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            //'appuid' => $this->_appuid,
            'display_name' => $this->_display_name,
            'client_id' => $this->_client_id,
            'client_secret' => $this->_client_secret,
            'client_type' => APPFormat::encodeClientType($this->_developer_type,$this->_client_type),
            'reg_area' => $this->_reg_area,
            'avatar' => $this->avatar_md5,
            'redirect_uri' => $this->redirect_uri
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

    public function delete() : void{
        if($this->_createNewAppEntity){
            $this->_createNewAppEntity = false;
            return;
        }
        //Delete existing record row of this app
        {
            $this->_Database->where('appuid',$this->_appuid);
            $updateRst = $this->_Database->delete('app_infos');
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
        //Delete existing record row of managements of this app
        {
            $this->_Database->where('appuid',$this->_appuid);
            $updateRst = $this->_Database->delete('app_manage_infos');
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
            $this->_managementRelations = NULL;
        }
        //TODO: Delete any related OAuth DB storage.
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

        $actualClientID = strtolower($actualClientID);

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

        $actualClientSecret = strtolower($actualClientSecret);

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
        $returnObj->_redirect_uri = NULL;

        $returnObj->_createNewAppEntity = true;
        $returnObj->saveToDatabase();

        $returnObj->_managementRelations = APPManagementRelations::createAppManagementRelations(
            $Database,
            $returnObj,
            $owner
        );
        $returnObj->_managementRelations->saveToDatabase();
        return $returnObj;
    }

    public static function fromClientID(MysqliDb $Database, string $clientID) : AppEntity{
        if(!APPFormat::checkClientID($clientID)){
            throw new PDKException(30002,'ClientID format incorrrect',array('credential'=>'client_id'));
        }
        $clientID = strtolower($clientID);
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
        $returnObj->_managementRelations = APPManagementRelations::fromAPPUID(
            $Database,
            $returnObj->getAppUID()
        );
        return $returnObj;
    }

    public static function fromAPPUID(MysqliDb $Database, int $appUID) : AppEntity{
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
        $returnObj->_managementRelations = APPManagementRelations::fromAPPUID(
            $Database,
            $returnObj->getAppUID()
        );
        return $returnObj;
    }

    public static function checkClientIDExist(MysqliDb $Database, string $clientID) : bool{
        $clientID = strtolower($clientID);
        $Database->where('client_id',$clientID);
        $count = $Database->getValue('app_infos','count(*)');
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