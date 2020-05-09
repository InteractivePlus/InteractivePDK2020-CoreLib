<?php
namespace InteractivePlus\PDK2020Core\Settings;

function getPDKSetting(string $item){
    return constant(__NAMESPACE__ . '\\' . $item);
}

function setPDKSetting(string $item, $value) : void{
    define(__NAMESPACE__ . '\\' . $item, $value);
}

setPDKSetting('USERNAME_MINLEN',1);
setPDKSetting('USERNAME_MAXLEN', 20);
setPDKSetting('USERNAME_REGEX','');
setPDKSetting('DISPLAYNAME_MINLEN', 1);
setPDKSetting('DISPLAYNAME_MAXLEN', 15);
setPDKSetting('DISPLAYNAME_REGEX','');
setPDKSetting('SIGNATURE_MAXLEN', 40);
setPDKSetting('SIGNATURE_REGEX','');
setPDKSetting('EMAIL_MAXLEN',50);
setPDKSetting('PASSWORD_MINLEN', 5);
setPDKSetting('PASSWORD_MAXLEN', 40);
setPDKSetting('PASSWORD_REGEX','');
setPDKSetting('LOGIN_SINGLEIPMAXTRIAL_COUNT', 2);
setPDKSetting('LOGIN_SINGLEIPMAXTRIAL_DURATION', 120); //2 mins
setPDKSetting('AVATOR_MAX_SIZE', 200); //In kB
setPDKSetting('PASSWORD_SALT', "");
setPDKSetting('TOKEN_SALT', "");
setPDKSetting('VERIFICATION_CODE_SALT', "");
setPDKSetting('TOKEN_AVAILABLE_DURATION', 3600);
setPDKSetting('VERIFICATION_CODE_AVAILABLE_DURATION', 300); //5 mins
setPDKSetting('DEFAULT_COUNTRY','CN');
setPDKSetting('DEFAULT_LOCALE','zh_CN');