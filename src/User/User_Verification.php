<?php
namespace InteractivePlus\PDK2020Core\User;
class User_Verification{
    protected static function verifyString(string $str, int $minLength, int $maxLength, string $regex = null) : bool{
        $strLength = strlen($str);
        //Check length first
        if(
            $strLength < $minLength
             || $strLength > $maxLength
        ){
            return false;
        }
        if(empty($regex)){
            return true;
        }else{
            return preg_match($regex,$str) ? true : false;
        }
    }
    public static function verifyUsername(string $username) : bool{
        return self::verifyString(
            $username,
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('USERNAME_MINLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('USERNAME_MAXLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('USERNAME_REGEX')
        );
    }
    public static function verifyDisplayName(string $displayName) : bool{
        return self::verifyString(
            $displayName,
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('DISPLAYNAME_MINLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('DISPLAYNAME_MAXLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('DISPLAYNAME_REGEX')
        );
    }
    public static function verifySignature(string $signature) : bool{
        return self::verifyString(
            $signature,
            0,
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('SIGNATURE_MAXLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('SIGNATURE_REGEX')
        );
    }
    public static function verifyPassword(string $password) : bool{
        return self::verifyString(
            $password,
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('PASSWORD_MINLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('PASSWORD_MAXLEN'),
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('PASSWORD_REGEX')
        );
    }
    public static function verifyEmail(string $email) : bool{
        return self::verifyString(
            $email,
            3,//x@x
            \InteractivePlus\PDK2020Core\Settings\getPDKSetting('EMAIL_MAXLEN'),
            '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/'
        );
    }
    public static function verifyPhoneNumber(string $phone, string $country = '') : bool{
        if(empty($phone) || strlen($phone) < 1 || strlen($phone) > 13 * 2){
            return false;
        }
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        if(empty($country)){
            $country = \InteractivePlus\PDK2020Core\Settings\getPDKSetting('DEFAULT_COUNTRY');
            if(empty($country)){ //if in the configuration the default country is empty, set default country to null.
                $country = null;
            }
        }
        $parsedNumber = null;
        try{
            $parsedNumber = $phoneNumberUtil->parse($phone,$country);
        }catch(\libphonenumber\NumberParseException $e){
            return false;
        }
        if(!$phoneNumberUtil->isValidNumber($parsedNumber)){
            return false;
        }
        return true;
    }
    public static function verifyPhoneNumberObj(\libphonenumber\PhoneNumber $phone) : bool{
        if($phone === NULL){
            return false;
        }
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        $parsedNumber = $phone;
        if(!$phoneNumberUtil->isValidNumber($parsedNumber)){
            return false;
        }
        return true;
    }
}