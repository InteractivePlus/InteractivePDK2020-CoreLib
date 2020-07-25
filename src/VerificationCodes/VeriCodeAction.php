<?php
namespace InteractivePlus\PDK2020Core\VerificationCodes;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;

class VeriCodeAction{
    public static function executeAutomaticAction(VeriCode $veriCode, string $RemoteIPAddr){
        $relatedUser = $veriCode->getUser();
        switch($veriCode->actionID){
            case 10001:
                if(empty($relatedUser->getEmail())){
                    throw new PDKException(
                        80005,
                        'Verification Code Action Execution Error',
                        array('errMsg'=>'User Email Address is empty')
                    );
                }
                $relatedUser->email_verified = true;
                $relatedUser->saveToDatabase();
            break;
            case 10002:
                if($relatedUser->getPhoneNumber() === NULL){
                    throw new PDKException(
                        80005,
                        'Verification Code Action Execution Error',
                        array('errMsg'=>'User Phone Number is empty')
                    );
                }
                $relatedUser->phone_verified = true;
                $relatedUser->saveToDatabase();
            break;
            case 20002:
                
        }
    }
    public static function verifyActionExecutable(VeriCode $veriCode, string $RemoteAddr) : bool{
        $relatedUser = $veriCode->getUser();
        
    }
}