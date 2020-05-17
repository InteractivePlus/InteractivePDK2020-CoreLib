<?php
namespace InteractivePlus\PDK2020Core\VerificationCodes;
class SentMethod{
    const NOTSENT = 0;
    const EMAIL = 1;
    const MOBILETEXT = 2;
    public static function isSentMethod(int $method) : bool{
        switch($method){
            case self::MOBILETEXT:
            case self::EMAIL:
            case self::NOTSENT:
                return true;
            default:
                return false;
        }
    }
    public static function fixMethod(int $method) : int{
        if(!self::isSentMethod($method)){
            return self::NOTSENT;
        }else{
            return $method;
        }
    }
}