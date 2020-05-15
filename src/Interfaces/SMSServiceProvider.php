<?php
namespace InteractivePlus\PDK2020Core\Interfaces;

use libphonenumber\PhoneNumber;

abstract class SMSServiceProvider{
    public abstract function addToAccount(PhoneNumber $phoneNumber, string $name = '') : void;
    public abstract function clearToAccount() : void;
    public abstract function setBody(string $body = '') : void;
    public abstract function setFromName(string $fromName = '') : void;
    public function clear(){
        $this->clearToAccount();
        $this->setBody();
        $this->setFromName();
    }
    public abstract function send() : bool;
}