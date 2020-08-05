<?php
namespace InteractivePlus\PDK2020Core\Apps;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\User\User;
use InteractivePlus\PDK2020Core\Utils\DataUtil;
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

    public function getAPPUID() : int{
        return $this->_appuid;
    }

    public function getAPP() : AppEntity{
        return AppEntity::fromAPPUID($this->_Database,$this->_appuid);
    }

    public function getOwnerUID() : int{
        return $this->_owner;
    }

    public function getOwner() : User{
        return User::fromUID($this->_Database, $this->_owner);
    }

    public function setOwner(User $owner) : void{
        $uid = $owner->getUID();
    }

    public function getUserUIDRole(int $uid) : int{
        if($this->_owner === $uid){
            return APPManagementPermissionTypes::OWNER;
        }
        $otherPerm = $this->_otherPermissionList[$uid];
        if($otherPerm === NULL){
            return APPManagementPermissionTypes::PERMISSION_DENIED;
        }else{
            return $otherPerm;
        }
    }

    public function getListWithoutOwner() : array{
        return $this->_otherPermissionList;
    }

    public function getUserRole(User $user) : int{
        return $this->getUserUIDRole($user->getUID());
    }

    public function deleteUserUIDFromList(int $uid) : bool{
        $keyVal = $this->_otherPermissionList[$uid];
        if($keyVal !== NULL){
            unset($this->_otherPermissionList[$uid]);
            return true;
        }else{
            return false;
        }
    }

    public function deleteUserFromList(User $user) : bool{
        return $this->deleteUserUIDFromList($user->getUID());
    }

    public function setUserRole(User $user, int $role){
        $role = APPManagementPermissionTypes::fixPermissionType($role);
        switch($role){
            case APPManagementPermissionTypes::OWNER:
                $this->setOwner($user);
            break;
            case APPManagementPermissionTypes::PERMISSION_DENIED:
                $this->deleteUserFromList($user);
            break;
            default:
            $this->_otherPermissionList[$user->getUID()] = $role;
        }
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
                    $this->_otherPermissionList[$singleRow['uid']] = $singleRow['role'];
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
        foreach($this->_otherPermissionList as $permItemKey => $permItemVal){
            $returnArr[] = array(
                'appuid' => $this->_appuid,
                'uid' => $permItemKey,
                'role' => $permItemVal
            );
        }

        return $returnArr;
    }
    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . ' class');
        }
        
        $newDataArray = $this->saveToDataArray();
        $addArray = array();
        $deleteArray = array();
        $changeArray = array();


        if(empty($this->_lastDataArray)){
            $addArray = $newDataArray;
        }else{
            $oldDataArray = $this->_lastDataArray;
            DataUtil::compareDataRowsChange(
                $newDataArray,
                $oldDataArray,
                $changeArray,
                $deleteArray,
                $addArray,
                'uid'
            );
        }

        //add to database
        foreach($addArray as $eachItemToAdd){
            $insertedID = $this->_Database->insert('app_manage_infos',$eachItemToAdd);
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
        }

        //delete from database
        foreach($deleteArray as $eachItemToDel){
            $this->_Database->where('appuid',$this->_appuid);
            $this->_Database->where('uid',$eachItemToDel['uid']);
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
        }

        //update database
        foreach($changeArray as $eachItemToUpdate){
            $this->_Database->where('appuid',$this->_appuid);
            $this->_Database->where('uid',$eachItemToUpdate['after']['uid']);
            $newRowArray = array(
                'role' => $eachItemToUpdate['after']['role']
            );
            $updateRst = $this->_Database->update('app_manage_infos',$newRowArray);
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
        
        $this->_dataTime = time();
        $this->_lastDataArray = $newDataArray;
    }
    protected function insertToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . ' class');
        }
        $dataArray = $this->saveToDataArray();

        foreach($dataArray as $rowToAdd){
            $insertedID = $this->_Database->insert('app_manage_infos',$rowToAdd);
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
        }
        $this->_dataTime = time();
        $this->_lastDataArray = $dataArray;
    }
    public function saveToDatabase() : void{
        if($this->_createNewRelation){
            $this->insertToDatabase();
            $this->_createNewRelation = false;
        }else{
            $this->updateToDatabase();
        }
    }
    
    public static function createAppManagementRelations(
        MysqliDb $Database,
        AppEntity $app,
        User $owner
    ) : APPManagementRelations{
        //check replication of clientID first
        if(self::checkAPPUIDExist($Database,$app->getAppUID())){
            throw new PDKException(20006, 'APP Management Relations already exist');
            return;
        }

        $returnObj = new APPManagementRelations();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_appuid = $app->getAppUID();
        $returnObj->_owner = $owner->getUID();
        $returnObj->_otherPermissionList = array();

        $returnObj->_createNewRelation = true;
        return $returnObj;
    }

    public static function fromAPPUID(MysqliDb $Database, int $appUID) : APPManagementRelations{
        $Database->where('appuid',$appUID);
        $dataRows = $Database->get('app_manage_infos');
        if(!$dataRows){
            throw new PDKException(20007,'APP Management Relations non-existant');
        }
        $returnObj = new APPManagementRelations();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRows;
        $returnObj->_createNewRelation = false;
        $returnObj->readFromDataRows($dataRows);
        return $returnObj;
    }

    public static function checkAPPUIDExist(MysqliDb $Database, int $appuid) : bool{
        $Database->where('appuid',$appuid);
        $count = $Database->getValue('app_manage_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }
}