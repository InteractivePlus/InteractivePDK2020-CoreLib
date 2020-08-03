<?php
namespace InteractivePlus\PDK2020Core\Apps;
class AppClientType{
    const PUBLIC_CLIENT = 1;
    const PRIVATE_CLIENT = 2;

    public static function isClientType(int $type) : bool{
        switch($type){
            case self::PUBLIC_CLIENT:
            case self::PRIVATE_CLIENT:
                return true;
            default:
                return false;
        }
    }
    public static function fixClientType(int $type) : int{
        if(!self::isClientType($type)){
            return self::PRIVATE_CLIENT;
        }else{
            return $type;
        }
    }
}