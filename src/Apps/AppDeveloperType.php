<?php
namespace InteractivePlus\PDK2020Core\Apps;
class AppDeveloperType{
    const FIRST_PARTY = 1;
    const TRUSTED_THIRD_PARTY = 2;
    const THIRD_PARTY = 3;

    public static function isDeveloperType(int $type) : bool{
        switch($type){
            case self::FIRST_PARTY:
            case self::TRUSTED_THIRD_PARTY:
            case self::THIRD_PARTY:
                return true;
            default:
                return false;
        }
    }
    public static function fixClientType(int $type) : int{
        if(!self::isDeveloperType($type)){
            return self::THIRD_PARTY;
        }else{
            return $type;
        }
    }
}