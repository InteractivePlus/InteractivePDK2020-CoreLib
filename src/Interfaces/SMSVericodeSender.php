<?php
namespace InteractivePlus\PDK2020Core\Interfaces;

use InteractivePlus\PDK2020Core\VerificationCodes\VeriCode;
use libphonenumber\PhoneNumber;

interface SMSVericodeSender{
    public function sendVerificationCode(
        VeriCode $code, 
        PhoneNumber $phoneNumber, 
        string $toName = '', 
        string $fromName = ''
    ) : void;
}