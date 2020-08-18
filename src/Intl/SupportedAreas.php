<?php
namespace InteractivePlus\PDK2020Core\Intl;
class SupportedAreas{
    const CN = 'CN';
    const US = 'US';
    public static function isSupportedArea(string $area) : bool{
        switch($area){
            case self::CN:
            case self::US:
                return true;
            default:
                return false;
        }
    }
    public static function fixSupportedArea(string $area) : string{
        if(!self::isSupportedArea($area)){
            return self::US;
        }else{
            return $area;
        }
    }
}