<?php
namespace InteractivePlus\PDK2020Core\Formats;

use InteractivePlus\PDK2020Core\Settings\Setting;

class PasswordFormat{
    public static function verifyPassword(string $password) : bool{
        return UserFormat::verifyPassword($password);
    }
    public static function checkPassword(string $password, string $password_hash) : bool{
        if(self::encryptPassword($password) == $password_hash){
            return true;
        }else{
            return false;
        }
    }
    public static function checkPasswordHash(string $passwordHash, string $stored_password_hash) : bool{
        if(strtoupper($passwordHash) === strtoupper($stored_password_hash)){
            return true;
        }else{
            return false;
        }
    }
    public static function encryptPassword(string $password) : string{
        return strtoupper(hash('sha256',$password . Setting::PASSWORD_SALT));
    }
}