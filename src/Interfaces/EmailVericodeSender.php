<?php
namespace InteractivePlus\PDK2020Core\Interfaces;
interface EmailVericodeSender{
    public function sendVerificationCode(\InteractivePlus\PDK2020Core\VerificationCodes\VeriCode $verificationCode) : void;
}