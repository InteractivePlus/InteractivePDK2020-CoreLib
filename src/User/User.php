<?php
namespace InteractivePlus\PDK2020Core\User;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\UserGroup\UserGroup;
use MysqliDb;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;
use libphonenumber\PhoneNumber;

class User{
    protected $_Database;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_username = NULL;
    private $_display_name = NULL;
    private $_signature = NULL;
    private $_password_hash = NULL;
    private $_email = NULL;
    private $_phone_number = NULL;
    private $_settings_array = array();
    public $email_verified = false;
    public $phone_verified = false;
    private $_permission_override_array = array();
    private $_group = NULL;
    public $regtime = 0;
    public $reg_client_addr = NULL;
    public $is_admin = false;
    public $avatar_md5 = NULL;
    public $is_frozen = false;

    private $_createNewUser = false;

    private function __construct(){

    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getUsername() : string{
        return $this->_username;
    }

    public function changeUserName(string $newUserName) : void{
        if(!User_Verification::verifyUsername($newUserName)){
            throw new PDKException(30002,'Username format incorrect',array('credential'=>'username'));
        }
        if($newUserName == $this->_username){
            return;
        }
        if(self::checkUsernameExist($this->_Database,$newUserName)){
            throw new PDKException(10004,'Username already exist');
        }
        if(!$this->_createNewUser){
            //Update Database
            $this->_Database->where('username',$this->_username);
            $differenceArray = array(
                'username' => $newUserName
            );
            $updateRst = $this->_Database->update('user_infos',$differenceArray);
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    'User update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
        }
        $this->_username = $newUserName;
    }

    public function getDisplayName() : string{
        return $this->_display_name;
    }

    public function setDisplayName(string $displayName) : void{
        if($this->_display_name == $displayName){
            return;
        }
        if(!User_Verification::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display name format incorrect',array('credential'=>'display_name'));
        }
        if(self::checkDisplayNameExist($this->_Database, $displayName)){
            throw new PDKException(10007,'Display name already exist');
        }
        $this->_display_name = $displayName;
    }

    public function getSignature() : string{
        return $this->_signature;
    }

    public function setSignature(string $signature) : void{
        if(!User_Verification::verifySignature($signature)){
            throw new PDKException(30002,'Signature format incorrect',array('credential'=>'signature'));
        }
        $this->_signature = $signature;
    }

    public function getPasswordHash() : string{
        return $this->_password_hash;
    }

    public function checkPassword(string $password) : bool{
        return Password::checkPassword($password,$this->_password_hash);
    }

    public function setPassword(string $password) : void{
        if(!Password::verifyPassword($password)){
            throw new PDKException(30002,'Password format incorrect',array('credential'=>'password'));
        }
        $this->_password_hash = Password::encryptPassword($password);
    }

    public function setPasswordHash(string $passwordHash) : void{
        $this->_password_hash = $passwordHash;
    }

    public function getEmail() : string{
        return $this->_email;
    }

    public function setEmail(string $email) : void{
        if($email == $this->_email){
            return;
        }
        if(!User_Verification::verifyEmail($email)){
            throw new PDKException(30002,'Email format incorrect',array('credential'=>'email'));
        }
        if(!empty($email) && self::checkEmailExist($this->_Database,$email)){
            throw new PDKException(10005,'Email already exist');
        }
        $this->_email = $email;
    }

    public function getPhoneNumber() : \libphonenumber\PhoneNumber{
        return $this->_phone_number;
    }

    public function getPhoneNumberStr() : string{
        if($this->_phone_number === NULL){
            return NULL;
        }
        return UserPhoneNum::outputPhoneNumberE164($this->_phone_number);
    }
    
    public function getPhoneNumberStrFormatted() : string{
        if($this->_phone_number === NULL){
            return NULL;
        }
        return UserPhoneNum::outputPhoneNumberIntl($this->_phone_number);
    }

    public function setPhoneNumberObj(\libphonenumber\PhoneNumber $numberObj, string $country = ''){
        if(!UserPhoneNum::verifyPhoneNumberObj($numberObj,$country)){
            throw new PDKException(30002,'Phone number format incorrect',array('credential'=>'phone_number'));
        }
        if($this->_phone_number !== NULL && $numberObj !== NULL){
            if(UserPhoneNum::outputPhoneNumberE164($this->_phone_number) == UserPhoneNum::outputPhoneNumberE164($numberObj)){
                return;
            }
        }
        if($numberObj !== NULL && self::checkPhoneNumberExist($this->_Database,$numberObj)){
            throw new PDKException(10006,'Phone number already exist');
        }
        $this->_phone_number = $numberObj;
    }

    public function getSettings() : array{
        return $this->_settings_array;
    }

    public function setSettings(array $settings) : void{
        $this->_settings_array = $settings;
    }

    public function getSettingItem(string $key){
        return $this->_settings_array[$key];
    }

    public function setSettingItem(string $key, $value) : void{
        if(empty($value)){
            unset($this->_settings_array[$key]);
        }else{
            $this->_settings_array[$key] = $value;
        }
    }

    public function deleteSettingItem(string $key) : void{
        $this->setSettingItem($key,NULL);
    }

    public function getPermissionOverrides() : array{
        return $this->_permission_override_array;
    }

    public function setPermissionOverrides(array $overrides) : void{
        $this->_permission_override_array = $overrides;
    }

    public function getPermissionItem(string $key){
        $overrideVal = $this->getPermissionOverrideItem($key);
        if($overrideVal !== NULL){
            return $overrideVal;
        }
        if($this->getGroup() !== NULL){
            return $this->getGroup()->getPermissionItem($key);
        }else{
            return Setting::getPDKSetting('DEFAULT_GROUP_PERMISSION')[$key];
        }
    }

    public function getPermissionOverrideItem(string $key){
        return $this->_permission_override_array[$key];
    }

    public function setPermissionOverrideItem(string $key, $value) : void{
        if(empty($value)){
            unset($this->_permission_override_array[$key]);
        }else{
            $this->_permission_override_array[$key] = $value;
        }
    }

    public function deletePermissionOverrideItem(string $key) : void{
        $this->setPermissionOverrideItem($key,NULL);
    }

    public function getGroup() : UserGroup{
        if(empty($this->_group)){
            return NULL;
        }
        return UserGroup::fromGroupID($this->_Database,$this->_group);
    }

    public function setGroupObj(UserGroup $groupObject){
        if($groupObject === NULL){
            $this->_group = NULL;
        }else{
            $this->_group = $groupObject->getGroupID();
        }
    }

    public function readFromDataRow(array $DataRow) : void{
        $this->_username = $DataRow['username'];
        $this->_display_name = $DataRow['display_name'];
        $this->_signature = $DataRow['signature'];
        $this->_password_hash = $DataRow['password'];
        $this->_email = $DataRow['email'];

        //Phone Start
        $tempPhone = $DataRow['phone_number'];
        
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        try{
            $parsedObject = $phoneNumberUtil->parse($tempPhone,'CN');
            $this->_phone_number = $parsedObject;
        }catch(\libphonenumber\NumberParseException $e){
            $this->_phone_number = NULL;
        }
        //Phone End

        $this->_settings_array = empty($DataRow['settings']) ? array() : json_decode(gzuncompress($DataRow['settings']),true);
        $this->email_verified = $DataRow['email_verified'] == 1;
        $this->phone_verified = $DataRow['phone_verified'] == 1;
        $this->_permission_override_array = empty($DataRow['permission_override']) ? array() : json_decode(gzuncompress($DataRow['permission_override']),true);
        $this->_group = $DataRow['group'];
        $this->regtime = $DataRow['regtime'];
        $this->reg_client_addr = $DataRow['reg_client_addr'];
        $this->is_admin = $DataRow['is_admin'] == 1;
        $this->avatar_md5 = $DataRow['avatar'];
        $this->is_frozen = $DataRow['is_frozen'] == 1;
        $_dataTime = time();
    }

    public function saveToDataArray() : array{
        $savedPhoneNumber = NULL;
        if($this->_phone_number !== NULL){
            $savedPhoneNumber = UserPhoneNum::outputPhoneNumberE164($this->_phone_number);
        }
        $savedArray = array(
            'username' => $this->_username,
            'display_name' => $this->_display_name,
            'signature' => $this->_signature,
            'password' => $this->_password_hash,
            'email' => $this->_email,
            'phone_number' => $savedPhoneNumber,
            'settings' => empty($this->_settings_array) ? NULL : gzcompress(json_encode($this->_settings_array)),
            'email_verified' => $this->email_verified ? 1 : 0,
            'phone_verified' => $this->phone_verified ? 1 : 0,
            'permission_override' => empty($this->_permission_override_array) ? NULL : gzcompress(json_encode($this->_permission_override_array)),
            'group' => $this->_group,
            'regtime' => $this->regtime,
            'reg_client_addr' => $this->reg_client_addr,
            'is_admin' => $this->is_admin ? 1 : 0,
            'avatar' => $this->avatar_md5,
            'is_frozen' => $this->is_frozen ? 1 : 0
        );
        return $savedArray;
    }

    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in User class');
        }
        
        $newDataArray = $this->saveToDataArray();
        $differenceArray = array();
        if(empty($this->_lastDataArray)){
            $differenceArray = $newDataArray;
        }else{
            $oldDataArray = $this->_lastDataArray;
            $differenceArray = \InteractivePlus\PDK2020Core\Utils\DataUtil::compareDataArrayDifference($newDataArray,$oldDataArray);
        }
        $this->_Database->where('username',$this->_username);
        $updateRst = $this->_Database->update('user_infos',$differenceArray);
        if(!$updateRst){
            throw new PDKException(
                50007,
                'User update error',
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
            throw new PDKException(50006,'No database connection stored in User class');
        }
        $dataArray = $this->saveToDataArray();
        $insertedID = $this->_Database->insert('user_infos',$dataArray);
        if(!$insertedID){
            throw new PDKException(
                50007,
                'User insert error',
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
        if($this->_createNewUser){
            $this->insertToDatabase();
            $this->_createNewUser = false;
        }else{
            $this->updateToDatabase();
        }
    }

    public function delete() : void{
        //TODO: Finish this function
    }

    public static function createUser(
        MysqliDb $Database, 
        string $ipAddr,
        string $username, 
        string $password, 
        string $displayName,
        string $email = NULL,
        PhoneNumber $phone = NULL,
        bool $isAdmin = false
    ) : User{
        if(!User_Verification::verifyUsername($username)){
            throw new PDKException(30002,'Username format incorrect',array('credential'=>'username'));
        }
        if(!Password::verifyPassword($password)){
            throw new PDKException(30002,'Password format incorrect',array('credential'=>'password'));
        }
        if(!User_Verification::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display name format incorrect',array('credential'=>'display_name'));
        }
        if(!empty($email) && !User_Verification::verifyEmail($email)){
            throw new PDKException(30002,'Email format incorrect',array('credential'=>'email'));
        }
        if($phone !== NULL && !UserPhoneNum::verifyPhoneNumberObj($phone)){
            throw new PDKException(30002,'Phone number format incorrect',array('credential'=>'phone_number'));
        }
        if(self::checkUsernameExist($Database,$username)){
            throw new PDKException(10004,'Username already exist');
        }
        if(self::checkDisplayNameExist($Database, $displayName)){
            throw new PDKException(10007,'Display name already exist');
        }
        if(!empty($email) && self::checkEmailExist($Database,$email)){
            throw new PDKException(10005,'Email already exist');
        }
        if($phone !== NULL && self::checkPhoneNumberExist($Database,$phone)){
            throw new PDKException(10006,'Phone number already exist');
        }
        $returnObj = new User();
        
        $returnObj->_username = $username;
        $returnObj->setPassword($password);
        $returnObj->_display_name = $displayName;
        $returnObj->_email = $email;
        $returnObj->_phone_number = $phone;
        $returnObj->is_admin = $isAdmin;
        $returnObj->regtime = time();
        $returnObj->reg_client_addr = $ipAddr;
        $returnObj->_createNewUser = true;
        
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = array();
        return $returnObj;
    }

    public static function fromUsername(MysqliDb $Database, string $username) : User{
        if(!User_Verification::verifyUsername($username)){
            throw new PDKException(30002,'Username format incorrect',array('credential'=>'username'));
        }
        $Database->where('username',$username);
        $dataRow = $Database->getOne('user_infos');
        if(!$dataRow){
            throw new PDKException(10001,'User non-existant');
        }
        $returnObj = new User();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewUser = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function fromEmail(MysqliDb $Database, string $email) : User{
        if(!User_Verification::verifyEmail($email)){
            throw new PDKException(30002,'Email format incorrect',array('credential'=>'email'));
        }
        $Database->where('email',$email);
        $dataRow = $Database->getOne('user_infos');
        if(!$dataRow){
            throw new PDKException(10001,'User non-existant');
        }
        $returnObj = new User();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewUser = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function fromPhoneObj(MysqliDb $Database, \libphonenumber\PhoneNumber $PhoneObj) : User{
        if(!UserPhoneNum::verifyPhoneNumberObj($PhoneObj)){
            throw new PDKException(30002,'Phone number format incorrect',array('credential'=>'phone_number'));
        }
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $phoneNumberFormatted = $phoneNumberUtil->format($PhoneObj,\libphonenumber\PhoneNumberFormat::E164);
        $Database->where('phone_number',$phoneNumberFormatted);
        $dataRow = $Database->getOne('user_infos');
        if(!$dataRow){
            throw new PDKException(10001,'User non-existant');
        }
        $returnObj = new User();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewUser = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function getSearchResults(MysqliDb $Database, string $username = '', string $email = '', string $phoneNumber = '', string $displayname = '', int $numLimit = -1, int $offset = 0, string $CONNECT_OPERATOR = 'AND') : MultipleQueryResult{
        $returnArr = array();
        if(!empty($username)){
            $Database->where('username','%' . $username . '%','LIKE', $CONNECT_OPERATOR);
        }
        if(!empty($email)){
            $Database->where('email','%' . $email . '%', 'LIKE', $CONNECT_OPERATOR);
        }
        if(!empty($phoneNumber)){
            $Database->where('phone_number', '%' . $phoneNumber . '%', 'LIKE', $CONNECT_OPERATOR);
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
        $resultArray = $Database->withTotalCount()->get('user_infos',$limitParam);
        if($Database->count <= 0){
            return new MultipleQueryResult($offset,0,$Database->totalCount,NULL);
        }

        $dataTime = time();
        
        $userObjArr = array();
        foreach($resultArray as $singleRow){
            $userObj = new User();
            $userObj->_Database = $Database;
            $userObj->_dataTime = $dataTime;
            $userObj->_lastDataArray = $singleRow;
            $userObj->_createNewUser = false;
            $userObj->readFromDataRow($singleRow);
            $userObjArr[] = $userObj;
        }
        return new MultipleQueryResult($offset,$Database->count,$Database->totalCount,$userObjArr);
    }

    public static function checkUsernameExist(MysqliDb $Database, string $Username) : bool{
        $Database->where('username',$Username);
        $count = $Database->getValue('user_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkEmailExist(MysqliDb $Database, string $Email) : bool{
        $Database->where('email',$Email);
        $count = $Database->getValue('user_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkPhoneNumberExist(MysqliDb $Database, \libphonenumber\PhoneNumber $phoneNumber) : bool{
        $phoneNumberFormatted = UserPhoneNum::outputPhoneNumberE164($phoneNumber);
        $Database->where('phone_number',$phoneNumberFormatted);
        $count = $Database->getValue('user_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkDisplayNameExist(MysqliDb $Database, string $displayName) : bool{
        $Database->where('display_name',$displayName);
        $count = $Database->getValue('user_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }


    public static function getUserCount(MysqliDb $Database) : int{
        $stats = $Database->getValue("user_infos", "count(*)");
        return $stats;
    }
}