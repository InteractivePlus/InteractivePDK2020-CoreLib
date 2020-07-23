<?php
namespace InteractivePlus\PDK2020Core\Interfaces;

use InteractivePlus\PDK2020Core\VerificationCodes\VeriCode;

interface EmailVericodeSender{
    public function sendVerificationCode(
        VeriCode $verificationCode, 
        string $LOCALE_OVERRIDE = NULL
    ) : void;
}