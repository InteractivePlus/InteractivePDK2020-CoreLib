<?php
namespace InteractivePlus\PDK2020Core\User;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;

class User{
    protected $_Database;
    private int $_dataTime = 0;
    private $lastDataArray = NULL;

    private $_username;
    private $_display_name;
    private $_signature;
    private $_password_hash;
    private $_email;
    private $_phone_number;
    private $_settings_array;
    private $_thirdauth_array;
    public $email_verified = false;
    public $phone_verified = false;
    private $_permission_override_array;
    private $_group;
    public $regtime;
    private $_related_apps_array;
    public $is_admin;
    public $avatar_md5;
    public $is_frozen;
    
    public function available() : bool{
        return $this->_dataTime !== 0;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getUsername() : string{
        return $this->_username;
    }

    public function getDisplayName() : string{
        return $this->_display_name;
    }

    public function setDisplayName(string $displayName) : void{
        if(!User_Verification::verifyDisplayName($displayName)){
            throw new PDKException(30002,'User format incorrect');
        }
        $this->_display_name = $displayName;
    }

    public function getSignature() : string{
        return $this->_signature;
    }

    public function setSignature(string $signature) : void{
        if(!User_Verification::verifySignature($signature)){
            throw new PDKException(30002,'Signature format incorrect');
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
            throw new PDKException(30002,'Password format incorrect');
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
        if(!User_Verification::verifyEmail($email)){
            throw new PDKException(30002,'Email format incorrect');
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
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        return $phoneNumberUtil->format($this->_phone_number,\libphonenumber\PhoneNumberFormat::E164);
    }
    
    public function getPhoneNumberStrFormatted() : string{
        if($this->_phone_number === NULL){
            return NULL;
        }
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        return $phoneNumberUtil->format($this->_phone_number,\libphonenumber\PhoneNumberFormat::INTERNATIONAL);
    }

    public function setPhoneNumber(string $numberStr, string $country = ''){
        if(!User_Verification::verifyPhoneNumber($numberStr,$country)){
            throw new PDKException(30002,'Phone number format incorrect');
        }
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        if(empty($country)){
            $country = \InteractivePlus\PDK2020Core\Settings\getPDKSetting('DEFAULT_COUNTRY');
            if(empty($country)){ //if in the configuration the default country is empty, set default country to null.
                $country = null;
            }
        }
        $this->_phone_number = $phoneNumberUtil->parse($numberStr,$country);
    }

    public function setPhoneNumberObj(\libphonenumber\PhoneNumber $numberObj, string $country = ''){
        if(!User_Verification::verifyPhoneNumberObj($numberObj,$country)){
            throw new PDKException(30002,'Phone number format incorrect');
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

    public function getThirdAuths() : array{
        return $this->_thirdauth_array;
    }

    public function setThirdAuths(array $thirdAuths) : void{
        $this->_thirdauth_array = $thirdAuths;
    }

    public function getThirdAuthItem(string $appName){
        return $this->_thirdauth_array[$appName];
    }

    public function setThirdAuthItem(string $appName, $value) : void{
        if(empty($value)){
            unset($this->_thirdauth_array[$appName]);
        }else{
            $this->_thirdauth_array[$appName] = $value;
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
        $this->_thirdauth_array = empty($DataRow['thirdauth']) ? array() : json_decode(gzuncompress($DataRow['thirdauth']),true);
        $this->email_verified = $DataRow['email_verified'] == 1;
        $this->phone_verified = $DataRow['phone_verified'] == 1;
        $this->_permission_override_array = empty($DataRow['permission_override']) ? array() : json_decode(gzuncompress($DataRow['permission_override']),true);
        $this->_group = $DataRow['group'];
        $this->regtime = $DataRow['regtime'];
        $this->_related_apps_array = empty($DataRow['related_apps']) ? array() : json_decode(gzuncompress($DataRow['related_apps']),true);
        $this->is_admin = $DataRow['is_admin'] == 1;
        $this->avatar_md5 = $DataRow['avatar'];
        $this->is_frozen = $DataRow['is_frozen'] == 1;
        $_dataTime = time();
    }

    public function saveToDataArray() : array{
        $savedPhoneNumber = NULL;
        if($this->_phone_number !== NULL){
            $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            $savedPhoneNumber = $phoneNumberUtil->format($this->_phone_number,\libphonenumber\PhoneNumberFormat::E164);
        }
        $savedArray = array(
            'username' => $this->_username,
            'display_name' => $this->_display_name,
            'signature' => $this->_signature,
            'password' => $this->_password_hash,
            'email' => $this->_email,
            'phone_number' => $savedPhoneNumber,
            'settings' => empty($this->_settings_array) ? NULL : gzcompress(json_encode($this->_settings_array)),
            'thirdauth' => empty($this->_thirdauth_array) ? NULL : gzcompress(json_encode($this->_thirdauth_array)),
            'email_verified' => $this->email_verified ? 1 : 0,
            'phone_verified' => $this->phone_verified ? 1 : 0,
            'permission_override' => empty($this->_permission_override_array) ? NULL : gzcompress(json_encode($this->_permission_override_array)),
            'group' => $this->_group,
            'regtime' => $this->regtime,
            'related_apps' => empty($this->_related_apps_array) ? NULL : gzcompress(json_encode($this->_related_apps_array)),
            'is_admin' => $this->is_admin ? 1 : 0,
            'avatar' => $this->avatar_md5,
            'is_frozen' => $this->is_frozen ? 1 : 0
        );
        return $savedArray;
    }
}