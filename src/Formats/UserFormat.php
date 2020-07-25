<?php
namespace InteractivePlus\PDK2020Core\Formats;
class UserFormat{
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
            \InteractivePlus\PDK2020Core\Settings\Setting::USERNAME_MINLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::USERNAME_MAXLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::USERNAME_REGEX
        );
    }
    public static function verifyDisplayName(string $displayName) : bool{
        return self::verifyString(
            $displayName,
            \InteractivePlus\PDK2020Core\Settings\Setting::DISPLAYNAME_MINLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::DISPLAYNAME_MAXLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::DISPLAYNAME_REGEX
        );
    }
    public static function verifySignature(string $signature) : bool{
        if(empty($signature)){
            return true;
        }
        return self::verifyString(
            $signature,
            0,
            \InteractivePlus\PDK2020Core\Settings\Setting::SIGNATURE_MAXLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::SIGNATURE_REGEX
        );
    }
    public static function verifyPassword(string $password) : bool{
        return self::verifyString(
            $password,
            \InteractivePlus\PDK2020Core\Settings\Setting::PASSWORD_MINLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::PASSWORD_MAXLEN,
            \InteractivePlus\PDK2020Core\Settings\Setting::PASSWORD_REGEX
        );
    }
    public static function verifyEmail(string $email) : bool{
        return self::verifyString(
            $email,
            3,//x@x
            \InteractivePlus\PDK2020Core\Settings\Setting::EMAIL_MAXLEN,
            '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/'
        );
    }
}