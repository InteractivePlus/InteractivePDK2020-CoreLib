<?php
namespace InteractivePlus\PDK2020Core\Apps;
class APPManagementPermissionTypes{
    const OWNER = 5;
    const ADMIN = 4;
    const WRITE = 3;
    const MAINTAIN = 2;
    const READ = 1;
    const PERMISSION_DENIED = 0;
    public static function isPermissionType(int $type) : bool{
        switch($type){
            case self::OWNER:
            case self::ADMIN:
            case self::WRITE:
            case self::MAINTAIN:
            case self::READ:
                return true;
            break;
        }
        return false;
    }
    public static function fixPermissionType(int $type) : int{
        if(self::isPermissionType($type)){
            return $type;
        }
        return self::PERMISSION_DENIED;
    }
}