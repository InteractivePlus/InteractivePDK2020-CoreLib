<?php
namespace InteractivePlus\PDK2020Core\Utils;

use InteractivePlus\PDK2020Core\Intl\SupportedAreas;
use InteractivePlus\PDK2020Core\Intl\SupportedLanguages;
use InteractivePlus\PDK2020Core\Settings\Setting;

class IntlUtil{
    public static function fixCountry(string $country = '') : string{
        if(!empty($country)){
            return SupportedAreas::fixSupportedArea($country);
        }else{
            return Setting::DEFAULT_COUNTRY;
        }
    }
    public static function fixArea(string $area = '') : string{
        return self::fixCountry($area);
    }
    public static function fixLocale(string $locale = '') : string{
        if(!empty($locale)){
            return SupportedLanguages::fixSupportedLanguage($locale);
        }else{
            return Setting::DEFAULT_LOCALE;
        }
    }
    public static function getLocaleValue(array $valueArray, string $locale = ''){
        if($valueArray[$locale] === NULL){
            return $valueArray[Setting::DEFAULT_LOCALE];
        }else{
            return $valueArray[$locale];
        }
    }
    public static function getMultiLangVal(string $language, $multiLangValue){
        $actualVal = $multiLangValue[$language];
        if($actualVal === NULL){
            return $multiLangValue[Setting::DEFAULT_LOCALE];
        }else{
            return $actualVal;
        }
    }
}