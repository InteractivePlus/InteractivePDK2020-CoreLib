<?php
namespace InteractivePlus\PDK2020Core\VerificationCodes;
class VeriCodeUsedStage{
    const USED = 1;
    const VALID = 0;
    const INVALID = -1;
    public static function isStage(int $stage) : bool{
        switch($stage){
            case self::USED:
            case self::VALID:
            case self::INVALID:
                return true;
            default:
                return false;
        }
    }
    public static function fixStage(int $stage) : int{
        if(!self::isStage($stage)){
            return self::INVALID;
        }else{
            return $stage;
        }
    }
}