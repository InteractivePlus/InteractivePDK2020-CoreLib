<?php
namespace InteractivePlus\PDK2020Core\Interfaces;

use InteractivePlus\PDK2020Core\VerificationCodes\VeriCode;
use libphonenumber\PhoneNumber;

interface PhoneCallVericodeSender{
    public function sendVerificationCode(
        VeriCode $verificationCode, 
        PhoneNumber $phoneNumber, 
        string $LOCALE_OVERRIDE = NULL
    ) : void;
}