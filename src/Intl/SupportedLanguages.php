<?php
namespace InteractivePlus\PDK2020Core\Intl;
class SupportedLanguages{
    const zh_CN = "zh_CN";
    const en_US = "en_US";
    public static function isSupportedLanguage(string $language) : bool{
        switch($language){
            case self::zh_CN:
            case self::en_US:
                return true;
            default:
                return false;
        }
    }
    public static function fixSupportedLanguage(string $language) : string{
        if(!self::isSupportedLanguage($language)){
            return self::en_US;
        }else{
            return $language;
        }
    }
}