<?php
namespace InteractivePlus\PDK2020Core\Settings;

class Setting{
    public static function getPDKSetting(string $item){
        return constant(__NAMESPACE__ . '\\' . $item);
    }

    public static function setPDKSetting(string $item, $value) : void{
        define(__NAMESPACE__ . '\\' . $item, $value);
    }
}

Setting::setPDKSetting('USERNAME_MINLEN',1);
Setting::setPDKSetting('USERNAME_MAXLEN', 20);
Setting::setPDKSetting('USERNAME_REGEX','');
Setting::setPDKSetting('DISPLAYNAME_MINLEN', 1);
Setting::setPDKSetting('DISPLAYNAME_MAXLEN', 15);
Setting::setPDKSetting('DISPLAYNAME_REGEX','');
Setting::setPDKSetting('SIGNATURE_MAXLEN', 40);
Setting::setPDKSetting('SIGNATURE_REGEX','');
Setting::setPDKSetting('EMAIL_MAXLEN',50);
Setting::setPDKSetting('PASSWORD_MINLEN', 5);
Setting::setPDKSetting('PASSWORD_MAXLEN', 40);
Setting::setPDKSetting('PASSWORD_REGEX','');
Setting::setPDKSetting('LOGIN_SINGLEIPMAXTRIAL_COUNT', 2);
Setting::setPDKSetting('LOGIN_SINGLEIPMAXTRIAL_DURATION', 120); //2 mins
Setting::setPDKSetting('AVATOR_MAX_SIZE', 200); //In kB
Setting::setPDKSetting('PASSWORD_SALT', "");
Setting::setPDKSetting('TOKEN_SALT', "");
Setting::setPDKSetting('VERIFICATION_CODE_SALT', "");
Setting::setPDKSetting('TOKEN_AVAILABLE_DURATION', 3600);
Setting::setPDKSetting('VERIFICATION_CODE_AVAILABLE_DURATION', 300); //5 mins
Setting::setPDKSetting('DEFAULT_COUNTRY','CN');
Setting::setPDKSetting('DEFAULT_LOCALE','zh_CN');
Setting::setPDKSetting(
    'DEFAULT_GROUP_PERMISSION',
    array(
        'createApp' => false,
        'numAppLimit' => 1
    )
);