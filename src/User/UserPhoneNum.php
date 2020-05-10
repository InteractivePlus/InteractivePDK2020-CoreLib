<?php
namespace InteractivePlus\PDK2020Core\User;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;

class UserPhoneNum{
    public static function parsePhone(string $Number, string $country) : PhoneNumber{
        if(empty($Number) || strlen($Number) < 1 || strlen($Number) > 13 * 2){
            return false;
        }
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        if(empty($country)){
            $country = \InteractivePlus\PDK2020Core\Settings\Setting::getPDKSetting('DEFAULT_COUNTRY');
            if(empty($country)){ //if in the configuration the default country is empty, set default country to null.
                $country = null;
            }
        }
        $parsedNumber = null;
        try{
            $parsedNumber = $phoneNumberUtil->parse($Number,$country);
        }catch(\libphonenumber\NumberParseException $e){
            throw new PDKException(30002,'Phone number format incorrect',array('credential'=>'phone_number'),$e);
        }
        return $parsedNumber;
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
    public static function outputPhoneNumberE164(PhoneNumber $phoneObj) : string{
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        return $phoneNumberUtil->format($phoneObj,PhoneNumberFormat::E164);
    }
    public static function outputPhoneNumberIntl(PhoneNumber $phoneObj) : string{
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();
        return $phoneNumberUtil->format($phoneObj,PhoneNumberFormat::INTERNATIONAL);
    }
}