<?php
namespace InteractivePlus\PDK2020Core\Apps;

use InteractivePlus\PDK2020Core\User\User;
use MysqliDb;

class APPManagementRelation{
    public $userUID = 0;
    private $_role = APPManagementPermissionTypes::READ;
    public function __construct(int $userUID, int $role){
        $this->userUID = $userUID;
        $this->_role = APPManagementPermissionTypes::fixPermissionType($role);
    }
    public function getRole() : int{
        return $this->_role;
    }
    public function setRole(int $role){
        $this->_role = APPManagementPermissionTypes::fixPermissionType($role);
    }
}