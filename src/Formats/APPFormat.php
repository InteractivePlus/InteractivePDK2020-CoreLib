<?php
namespace InteractivePlus\PDK2020Core\Formats;
class APPFormat{
    public static function checkClientID(string $clientID) : bool{
        if(strlen($clientID) === 32){
            return true;
        }else{
            return false;
        }
    }
    public static function checkClientSecret(string $clientSecret) : bool{
        if(strlen($clientSecret) === 64){
            return true;
        }else{
            return false;
        }
    }
    public static function generateClientID() : string{
        return bin2hex(random_bytes(16));
    }
    public static function generateClientSecret() : string{
        return bin2hex(random_bytes(32));
    }
}