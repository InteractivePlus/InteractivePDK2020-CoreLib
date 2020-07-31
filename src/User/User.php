<?php
namespace InteractivePlus\PDK2020Core\User;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\UserGroup\UserGroup;
use InteractivePlus\PDK2020Core\Utils\IntlUtil;
use InteractivePlus\PDK2020Core\Utils\UserPhoneNumUtil;
use MysqliDb;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;
use InteractivePlus\PDK2020Core\Formats\PasswordFormat;
use InteractivePlus\PDK2020Core\Formats\UserFormat;
use libphonenumber\PhoneNumber;
use League\OAuth2\Server\Entities\UserEntityInterface;

class User implements UserEntityInterface{
    protected $_Database;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_uid = -1;
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
    private $_area = NULL;
    private $_locale = NULL;

    private $_createNewUser = false;

    private function __construct(){

    }

    /**
     * @see UserEntityInterface
     * Return the user's identifier
     * @return int User identifier int(uid)
     */
    public function getIdentifier() : int
    {
        return $this->getUID();
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getUID() : int{
        return $this->_uid;
    }

    public function isFormalUser() : bool{
        return ($this->email_verified || $this->phone_verified);
    }

    public function getUsername() : string{
        return $this->_username;
    }

    public function changeUserName(string $newUserName) : void{
        if(!UserFormat::verifyUsername($newUserName)){
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
                    __CLASS__ . ' update error',
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
        if(!UserFormat::verifyDisplayName($displayName)){
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
        if(!UserFormat::verifySignature($signature)){
            throw new PDKException(30002,'Signature format incorrect',array('credential'=>'signature'));
        }
        $this->_signature = $signature;
    }

    public function getPasswordHash() : string{
        return $this->_password_hash;
    }

    public function checkPassword(string $password) : bool{
        return PasswordFormat::checkPassword($password,$this->_password_hash);
    }

    public function setPassword(string $password) : void{
        if(!PasswordFormat::verifyPassword($password)){
            throw new PDKException(30002,'Password format incorrect',array('credential'=>'password'));
        }
        $this->_password_hash = PasswordFormat::encryptPassword($password);
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
        if(!UserFormat::verifyEmail($email)){
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
        return UserPhoneNumUtil::outputPhoneNumberE164($this->_phone_number);
    }
    
    public function getPhoneNumberStrFormatted() : string{
        if($this->_phone_number === NULL){
            return NULL;
        }
        return UserPhoneNumUtil::outputPhoneNumberIntl($this->_phone_number);
    }

    public function setPhoneNumberObj(\libphonenumber\PhoneNumber $numberObj, string $country = ''){
        if(!UserPhoneNumUtil::verifyPhoneNumberObj($numberObj,$country)){
            throw new PDKException(30002,'Phone number format incorrect',array('credential'=>'phone_number'));
        }
        if($this->_phone_number !== NULL && $numberObj !== NULL){
            if(UserPhoneNumUtil::outputPhoneNumberE164($this->_phone_number) == UserPhoneNumUtil::outputPhoneNumberE164($numberObj)){
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
            return Setting::DEFAULT_GROUP_PERMISSION[$key];
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

    public function getArea() : string{
        return $this->_area;
    }
    
    public function setArea(string $area) : void{
        $this->_area = IntlUtil::fixArea($area);
    }

    public function getLocale() : string{
        return $this->_locale;
    }

    public function setLocale(string $locale) : void{
        $this->_locale = $locale;
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
            $parsedObject = $phoneNumberUtil->parse($tempPhone,Setting::DEFAULT_COUNTRY);
            $this->_phone_number = $parsedObject;
        }catch(\libphonenumber\NumberParseException $e){
            $this->_phone_number = NULL;
        }
        //Phone End

        $this->_uid = $DataRow['uid'];
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
        $this->_locale = $DataRow['locale'];
        $this->_area = $DataRow['area'];
        $_dataTime = time();
    }

    public function saveToDataArray() : array{
        $savedPhoneNumber = NULL;
        if($this->_phone_number !== NULL){
            $savedPhoneNumber = UserPhoneNumUtil::outputPhoneNumberE164($this->_phone_number);
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
            'is_frozen' => $this->is_frozen ? 1 : 0,
            'locale' => $this->_locale,
            'area' => $this->_area
        );
        return $savedArray;
    }

    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . 'class');
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
        $insertedID = $this->_Database->insert('user_infos',$dataArray);
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
        string $locale = Setting::DEFAULT_LOCALE,
        string $area = Setting::DEFAULT_COUNTRY,
        bool $isAdmin = false
    ) : User{
        if(!UserFormat::verifyUsername($username)){
            throw new PDKException(30002,'Username format incorrect',array('credential'=>'username'));
        }
        if(!PasswordFormat::verifyPassword($password)){
            throw new PDKException(30002,'Password format incorrect',array('credential'=>'password'));
        }
        if(!UserFormat::verifyDisplayName($displayName)){
            throw new PDKException(30002,'Display name format incorrect',array('credential'=>'display_name'));
        }
        if(!empty($email) && !UserFormat::verifyEmail($email)){
            throw new PDKException(30002,'Email format incorrect',array('credential'=>'email'));
        }
        if($phone !== NULL && !UserPhoneNumUtil::verifyPhoneNumberObj($phone)){
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
        $returnObj->_locale = IntlUtil::fixLocale($locale);
        $returnObj->_area = IntlUtil::fixArea($area);
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
        if(!UserFormat::verifyUsername($username)){
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

    public static function fromUID(MysqliDb $Database, int $uid) : User{
        if($uid <= 0){
            throw new PDKException(30002,'Username format incorrect',array('credential'=>'username'));
        }
        $Database->where('uid',$uid);
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
        if(!UserFormat::verifyEmail($email)){
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
        if(!UserPhoneNumUtil::verifyPhoneNumberObj($PhoneObj)){
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

    public static function checkUIDExist(MysqliDb $Database, int $uid) : bool{
        $Database->where('uid',$uid);
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
        $phoneNumberFormatted = UserPhoneNumUtil::outputPhoneNumberE164($phoneNumber);
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