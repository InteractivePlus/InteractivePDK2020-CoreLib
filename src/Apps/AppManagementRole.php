<?php
namespace InteractivePlus\PDK2020Core\Apps;
class AppManagementRole{
    const READ = 1;
    const MAINTAIN = 2;
    const WRITE = 3;
    const ADMIN = 4;
    const OWNER = 5;
    public static function isManagementRole(int $role) : bool{
        switch($role){
            case self::READ:
            case self::MAINTAIN:
            case self::WRITE:
            case self::ADMIN:
            case self::OWNER:
                return true;
            default:
                return false;
        }
    }
    public static function fixRole(int $role) : int{
        if(!self::isManagementRole($role)){
            return self::READ;
        }else{
            return $role;
        }
    }
}