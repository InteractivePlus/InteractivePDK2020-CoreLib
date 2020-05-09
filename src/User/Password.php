<?php
namespace InteractivePlus\PDK2020Core\User;
class Password{
    public static function verifyPassword(string $password) : bool{
        return User_Verification::verifyPassword($password);
    }
    public static function checkPassword(string $password, string $password_hash) : bool{
        if(self::encryptPassword($password) == $password_hash){
            return true;
        }else{
            return false;
        }
    }
    public static function encryptPassword(string $password) : string{
        return hash('sha256',$password . \InteractivePlus\PDK2020Core\Settings\getPDKSetting('PASSWORD_SALT'));
    }
}