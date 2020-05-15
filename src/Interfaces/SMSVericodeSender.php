<?php
namespace InteractivePlus\PDK2020Core\Interfaces;

use libphonenumber\PhoneNumber;

interface SMSVericodeSender{
    public function sendVerificationCode(string $veriCode, PhoneNumber $phoneNumber, string $toName = '', string $fromName = '');
}