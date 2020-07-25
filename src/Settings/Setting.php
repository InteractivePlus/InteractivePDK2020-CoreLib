<?php
namespace InteractivePlus\PDK2020Core\Settings;

class Setting{
    const USER_SYSTEM_NAME = array(
        'zh_CN' => '幽径',
        'en_US' => 'Solitary Trail'
    );
    const THIRD_PARTY_SYSTEM_NAME = array(
        'zh_CN' => '幽享',
        'en_US' => 'Solitary Share'
    );
    const USER_SYSTEM_LINKS = array(
        'zh_CN' => array(
            'confirm_email_url' => 'https://user.interactiveplus.org/zh_CN/veriLinks/verifyEmail/?code={{veri_code}}/',
            'confirm_phone_url' => 'https://user.interactiveplus.org/zh_CN/veriLinks/verifyPhone/?code={{veri_code}}/',
            'change_pwd_url' => 'https://user.interactiveplus.org/zh_CN/managements/changePassword/?code={{veri_code}}',
            'confirm_email_change_url' => 'https://user.interactiveplus.org/zh_CN/veriLinks/confirmEmailChange/?code={{veri_code}}',
            'confirm_phone_change_url' => 'https://user.interactiveplus.org/zh_CN/veriLinks/confirmPhoneChange/?code={{veri_code}}'
        ),
        'en_US' => array(
            'confirm_email_url' => 'https://user.interactiveplus.org/en_US/veriLinks/verifyEmail/?code={{veri_code}}/',
            'confirm_phone_url' => 'https://user.interactiveplus.org/en_US/veriLinks/verifyPhone/?code={{veri_code}}/',
            'change_pwd_url' => 'https://user.interactiveplus.org/en_US/managements/changePassword/?code={{veri_code}}',
            'confirm_email_change_url' => 'https://user.interactiveplus.org/en_US/veriLinks/confirmEmailChange/?code={{veri_code}}',
            'confirm_phone_change_url' => 'https://user.interactiveplus.org/en_US/veriLinks/confirmPhoneChange/?code={{veri_code}}'
        )
    );
    const THIRD_PARTY_SYSTEM_LINKS = array(

    );
    const USERNAME_MINLEN = 1;
    const USERNAME_MAXLEN = 20;
    const USERNAME_REGEX = '';
    const DISPLAYNAME_MINLEN = 1;
    const DISPLAYNAME_MAXLEN = 15;
    const DISPLAYNAME_REGEX = '';
    const SIGNATURE_MAXLEN = 40;
    const SIGNATURE_REGEX = '';
    const EMAIL_MAXLEN = 50;
    const PASSWORD_MINLEN = 5;
    const PASSWORD_MAXLEN = 40;
    const PASSWORD_REGEX = '';
    const AVATOR_MAX_SIZE = 200;//In kB
    const PASSWORD_SALT = '';
    const TOKEN_SALT = '';
    const VERIFICATION_CODE_SALT = '';
    const TOKEN_AVAILABLE_DURATION = 3600;
    const VERIFICATION_CODE_AVAILABLE_DURATION = 300;
    const DEFAULT_COUNTRY = 'CN';
    const DEFAULT_LOCALE = 'zh_CN';
    const DEFAULT_GROUP_PERMISSION = array(
        'createApp' => false,
        'numAppLimit' => 1
    );
    const ALLOW_TOKEN_IP_CHANGE = true;
    const ALLOW_VERICODE_IP_CHANGE = false;
    const DEBUG_MODE = true;
}