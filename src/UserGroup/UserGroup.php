<?php
namespace InteractivePlus\PDK2020Core\UserGroup;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\User\User_Verification;
use InteractivePlus\PDK2020Core\Utils\DataUtil;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;
use MysqliDb;

class UserGroup{
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_groupID = NULL;
    private $_parentGroupID = NULL;
    private $_displayName = NULL;
    private $_description = NULL;
    public $regtime = 0;
    private $_permissions = array();
    public $avatar_md5 = NULL;

    private $_createNewGroup = false;

    private function __construct(){

    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getGroupID() : string{
        return $this->_groupID;
    }

    public function changeGroupID(string $newGroupID) : void{
        if(!User_Verification::verifyUsername($newGroupID)){
            throw new PDKException(30002,'GroupID format incorrect',array('credential'=>'groupid'));
        }
        if($newGroupID == $this->_groupID){
            return;
        }
        if(self::checkGroupIDExist($this->_Database,$newGroupID)){
            throw new PDKException(60002,'GroupID already exist');
        }
        if(!$this->_createNewGroup){
            //Update Database
            $this->_Database->where('groupid',$this->_groupID);
            $differenceArray = array(
                'groupid' => $newGroupID
            );
            $updateRst = $this->_Database->update('usergroup_infos',$differenceArray);
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    'User group update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
        }
        $this->_username = $newGroupID;
    }

    public function getParentGroup() : UserGroup{
        if(empty($this->_parentGroupID)){
            return NULL;
        }
        return self::fromGroupID($this->_Database,$this->_parentGroupID);
    }

    public function setParentGroup(UserGroup $parentGroup) : void{
        if($parentGroup !== NULL){
            $this->_parentGroupID = $parentGroup->getGroupID();
        }else{
            $this->_parentGroupID = NULL;
        }
    }

    public function getDisplayName() : string{
        return $this->_displayName;
    }

    public function setDisplayName(string $displayName) : void{
        if(User_Verification::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display name format incorrect',array('credential'=>'display_name'));
        }
        if($this->_displayName == $displayName){
            return;
        }
        $this->_displayName = $displayName;
    }

    public function getDescription() : string{
        return $this->_description;
    }

    public function setDescription(string $description) : void{
        if(User_Verification::verifySignature($description)){
            throw new PDKException(30002,'Description format incorrrect', array('credential'=>'description'));
        }
        if($this->_description == $description){
            return;
        }
        $this->_description = $description;
    }

    public function getPermissions() : array{
        return $this->_permissions;
    }

    public function setPermissions(array $permissions){
        $this->_permissions = $permissions;
    }

    public function getPermissionItem(string $Key){
        if($this->_permissions[$Key] !== NULL){
            return $this->_permissions[$Key];
        }
        if($this->getParentGroup() === NULL){
            return Setting::getPDKSetting('DEFAULT_GROUP_PERMISSION')[$Key];
        }
        return $this->getParentGroup()->getPermissionItem($Key);
    }

    public function getPermissionOverrideItem(string $Key){
        return $this->_permissions[$Key];
    }

    public function setPermissionItem(string $Key, $value) : void{
        $this->_permissions[$Key] = $value;
    }

    public function readFromDataRow(array $DataRow) : void{
        $this->_groupID = $DataRow['groupid'];
        $this->_parentGroupID = $DataRow['parent_group_id'];
        $this->_displayName = $DataRow['display_name'];
        $this->_description = $DataRow['description'];
        $this->_regtime = $DataRow['regtime'];
        $this->_permissions = empty($DataRow['permissions']) ? array() : json_decode($DataRow['permissions'],true);
        $this->avatar_md5 = $DataRow['avatar'];
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            'groupid' => $this->_groupID,
            'parent_group_id' => $this->_parentGroupID,
            'display_name' => $this->_displayName,
            'description' => $this->_description,
            'regtime' => $this->_regtime,
            'permissions' => empty($this->_permissions) ? NULL : json_encode($this->_permissions),
            'avatar' => $this->avatar_md5
        );
        return $returnArr;
    }
    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in UserGroup class');
        }
        
        $newDataArray = $this->saveToDataArray();
        $differenceArray = array();
        if(empty($this->_lastDataArray)){
            $differenceArray = $newDataArray;
        }else{
            $oldDataArray = $this->_lastDataArray;
            $differenceArray = DataUtil::compareDataArrayDifference($newDataArray,$oldDataArray);
        }
        $this->_Database->where('groupid',$this->_groupID);
        $updateRst = $this->_Database->update('usergroup_infos',$differenceArray);
        if(!$updateRst){
            throw new PDKException(
                50007,
                'User group update error',
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
            throw new PDKException(50006,'No database connection stored in UserGroup class');
        }
        $dataArray = $this->saveToDataArray();
        $insertedID = $this->_Database->insert('usergroup_infos',$dataArray);
        if(!$insertedID){
            throw new PDKException(
                50007,
                'User group insert error',
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
        if($this->_createNewGroup){
            $this->insertToDatabase();
            $this->_createNewGroup = false;
        }else{
            $this->updateToDatabase();
        }
    }

    public function delete(UserGroup $newPlaceHolder = NULL) : void{
        if($this->_createNewGroup){
            $this->_createNewGroup = false;
            return;
        }
        $replacementGroupID = $newPlaceHolder === NULL ? NULL : $newPlaceHolder->getGroupID();
        //Update all parent_group_id in usergroup_infos and group in user_infos
        {
            $this->_Database->where('parent_group_id',$this->_groupID);
            $parent_group_update_array = array('parent_group_id'=>$replacementGroupID);
            $updateRst = $this->_Database->update('usergroup_infos',$parent_group_update_array);
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    'User group update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
        }
        {
            $this->_Database->where('group',$this->_groupID);
            $user_group_update_array = array('group'=>$replacementGroupID);
            $updateRst = $this->_Database->update('user_infos',$user_group_update_array);
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    'User group update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
        }
        //Delete existing record row of this groupID
        {
            $this->_Database->where('groupid',$this->_groupID);
            $updateRst = $this->_Database->delete('usergroup_infos');
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    'User group update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
        }
        //successfully deleted, now need to unset the UserGroup variable
    }

    public static function createUserGroup(
        MysqliDb $Database,
        string $groupID, 
        string $displayName,
        string $description,
        array $permissions,
        UserGroup $parent = NULL
    ) : UserGroup{
        if(!User_Verification::verifyUsername($groupID)){
            throw new PDKException(30002,'GroupID format incorrect',array('credential'=>'groupid'));
        }
        if(!User_Verification::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display name format incorrect',array('credential'=>'display_name'));
        }
        if(!User_Verification::verifySignature($description)){
            throw new PDKException(30002,'Description format incorrect',array('credential'=>'description'));
        }
        if(self::checkGroupIDExist($Database, $groupID)){
            throw new PDKException(60002,'User group already exist');
        }
        if(self::checkDisplayNameExist($Database,$displayName)){
            throw new PDKException(60003,'User group display name already exist');
        }
        $returnObj = new UserGroup();
        
        $returnObj->_groupID = $groupID;
        $returnObj->_display_name = $displayName;
        $returnObj->_description = $description;
        $returnObj->_permissions = empty($permissions) ? array() : $permissions;
        $returnObj->regtime = time();
        if($parent !== NULL){
            $returnObj->_parentGroupID = $parent->getGroupID();
        }
        $returnObj->_createNewGroup = true;
        
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = array();
        return $returnObj;
    }

    public static function fromGroupID(MysqliDb $Database, string $groupID) : UserGroup{
        if(!User_Verification::verifyUsername($groupID)){
            throw new PDKException(30002,'GroupID format incorrect',array('credential'=>'groupid'));
        }
        $Database->where('groupid',$groupID);
        $dataRow = $Database->getOne('usergroup_infos');
        if(!$dataRow){
            throw new PDKException(60001,'User group non-existant');
        }
        $returnObj = new UserGroup();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewGroup = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function getSearchResults(MysqliDb $Database, string $groupID = '', string $displayname = '', int $numLimit = -1, int $offset = 0, string $CONNECT_OPERATOR = 'AND') : MultipleQueryResult{
        $returnArr = array();
        if(!empty($groupID)){
            $Database->where('groupid','%' . $groupID . '%','LIKE', $CONNECT_OPERATOR);
        }
        if(!empty($displayname)){
            $Database->where('display_name','%' . $displayname . '%', 'LIKE', $CONNECT_OPERATOR);
        }
        $limitParam = NULL;
        if($numLimit !== -1 && $offset === 0){
            $limitParam = $numLimit;
        }else if($numLimit !== -1 && $offset !== 0){
            $limitParam = array($offset,$numLimit);
        }
        $resultArray = $Database->withTotalCount()->get('usergroup_infos',$limitParam);
        if($Database->count <= 0){
            return new MultipleQueryResult($offset,0,$Database->totalCount,NULL);
        }

        $dataTime = time();
        
        $userObjArr = array();
        foreach($resultArray as $singleRow){
            $userObj = new UserGroup();
            $userObj->_Database = $Database;
            $userObj->_dataTime = $dataTime;
            $userObj->_lastDataArray = $singleRow;
            $userObj->_createNewGroup = false;
            $userObj->readFromDataRow($singleRow);
            $userObjArr[] = $userObj;
        }
        return new MultipleQueryResult($offset,$Database->count,$Database->totalCount,$userObjArr);
    }

    public static function checkGroupIDExist(MysqliDb $Database, string $groupID) : bool{
        $Database->where('groupid',$groupID);
        $count = $Database->getValue('usergroup_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkDisplayNameExist(MysqliDb $Database, string $displayName) : bool{
        $Database->where('display_name',$displayName);
        $count = $Database->getValue('usergroup_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }
}