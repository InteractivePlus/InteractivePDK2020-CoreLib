<?php
namespace \InteractivePlus\PDK2020Core\User;
class User{
    protected $_Database;
    private int $_dataTime = 0;

    private $_username;
    public $display_name;
    public $signature;
    private $_password_hash;
    private $_email;
    private $_phone_numer;
    private $_settings_array;
    private $_thirdauth_array;
    public $email_verified = false;
    public $phone_verified = false;
    private $_permission_override_array;
    private $_group;
    public $regtime;
    private $_related_apps_array;
    public $is_admin;
    public $avatar_md5;
    
    public function available() : bool{
        return $_dataTime !== 0;
    }

    public function readFromDataRow(array $DataRow) : void{
        $this.$_username = $DataRow['username'];
        $this.$_display_name = $DataRow['display_name'];
        $this.$signature = $DataRow['signature'];
        $this.$_password_hash = $DataRow['password'];
        $this.$_email = $DataRow['email'];
        $this.$_phone_numer = $DataRow['phone_number'];
        $this.$_settings_array = empty($DataRow['settings']) ? array() : json_decode(gzuncompress($DataRow['settings']),true);
        $this.$_thirdauth_array = empty($DataRow['thirdauth']) ? array() : json_decode(gzuncompress($DataRow['thirdauth']),true);
        $this.$email_verified = $DataRow['email_verified'] == 1;
        $this.$phone_verified = $DataRow['phone_verified'] == 1;
        $this.$_permission_override_array = empty($DataRow['permission_override']) ? array() : json_decode(gzuncompress($DataRow['permission_override']),true);
        $this.$_group = $DataRow['group'];
        $this.$regtime = $DataRow['regtime'];
        $this.$_related_apps_array = empty($DataRow['related_apps']) ? array() : json_decode(gzuncompress($DataRow['related_apps']),true);
        $this.$is_admin = $DataRow['is_admin'] == 1;
        $this.$avatar_md5 = $DataRow['avatar'];
        $_dataTime = time();
    }

    public function saveToDataArray() : array{
        $savedArray = array(
            'username' => $this.$_username,
            'display_name' => $this.$_display_name,
            'signature' => $this.$signature,
            'password' => $this.$_password_hash,
            'email' => $this.$_email,
            'phone_number' => $this.$_phone_numer,
            'settings' => empty($this.$_settings_array) ? "" : gzcompress(json_encode($this.$_settings_array)),
            'thirdauth' => empty($this.$_thirdauth_array) ? "" : gzcompress(json_encode($this.$_thirdauth_array)),
            'email_verified' => $this.$email_verified ? 1 : 0,
            'phone_verified' => $this.$phone_verified ? 1 : 0,
            'permission_override' => empty($this.$_permission_override_array) ? "" : gzcompress(json_encode($this.$_permission_override_array)),
            'group' => $this.$_group,
            'regtime' => $this.$regtime,
            'related_apps' => empty($this.$_related_apps_array) ? "" : gzcompress(json_encode($this.$_related_apps_array)),
            'is_admin' => $this.$is_admin ? 1 : 0,
            'avatar' => $this.$avatar_md5
        );
        return $savedArray;
    }
}
?>