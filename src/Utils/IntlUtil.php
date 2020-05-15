<?php
namespace InteractivePlus\PDK2020Core\Utils;

use InteractivePlus\PDK2020Core\Settings\Setting;

class IntlUtil{
    public static function fixCountry(string $country = '') : string{
        if(!empty($country)){
            return $country;
        }else{
            return Setting::getPDKSetting('DEFAULT_COUNTRY');
        }
    }
    public static function fixLocale(string $locale = '') : string{
        if(!empty($locale)){
            return $locale;
        }else{
            return Setting::getPDKSetting('DEFAULT_LOCALE');
        }
    }
    public static function getLocaleValue(array $valueArray, string $locale = ''){
        if($valueArray[$locale] === NULL){
            return $valueArray[Setting::getPDKSetting('DEFAULT_LOCALE')];
        }else{
            return $valueArray[$locale];
        }
    }
}