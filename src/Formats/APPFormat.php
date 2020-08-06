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
        return strtolower(bin2hex(random_bytes(16)));
    }
    public static function generateClientSecret() : string{
        return strtolower(bin2hex(random_bytes(32)));
    }
    public static function encodeClientType(int $developerType, int $clientType) : int{
        return (($developerType - 1) * 2) + ($clientType-1);
    }
    public static function decodeClientType(int $encodedType, int &$developerType, int &$clientType) : void{
        $clientType = ($encodedType % 2) + 1;
        $developerType = intdiv($encodedType,2) + 1;
    }
}