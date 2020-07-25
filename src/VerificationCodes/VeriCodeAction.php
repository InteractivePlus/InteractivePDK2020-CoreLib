<?php
namespace InteractivePlus\PDK2020Core\VerificationCodes;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Interfaces\EmailVericodeSender;
use InteractivePlus\PDK2020Core\Interfaces\SMSVericodeSender;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\Utils\IntlUtil;
use InteractivePlus\PDK2020Core\Utils\UserPhoneNumUtil;

class VeriCodeAction{
    public static function executeAutomaticAction(VeriCode $veriCode, string $RemoteAddr, ?string $country = NULL, ?string $locale = NULL, ?EmailVericodeSender $emailVericodeSender = NULL){
        $country = IntlUtil::fixCountry($country);

        $errorMsg = '';
        if(!self::verifyActionExecutable($veriCode,$RemoteAddr,$errorMsg)){
            throw new PDKException(80005,'Verification Code Action Execution Error',array('errMsg'=>$errorMsg));
        }

        $relatedUser = $veriCode->getUser();
        switch($veriCode->actionID){
            case 10001:
                $relatedUser->email_verified = true;
                $relatedUser->saveToDatabase();
            break;
            case 10002:
                $relatedUser->phone_verified = true;
                $relatedUser->saveToDatabase();
            break;
            case 20001:
                //No action to execute, skip
            break;
            case 20002:
                $relatedUser->email_verified = false;
                $newEmail = $veriCode->getActionParam('new_email');
                if(empty($newEmail)){
                    $newEmail = NULL;
                }
                $relatedUser->setEmail($newEmail);
                $relatedUser->saveToDatabase();

                if($newEmail !== NULL){
                    //Generate new verify email vericode and send it
                    if($emailVericodeSender === NULL){
                        throw new PDKException(80005,'Verification Code Action Execution Error',array('errMsg'=>'VeriCodeSender Optional Param is NULL when needed'));
                    }
                    $verifyEmailVeriCode = VeriCode::createNewCode(
                        $veriCode->getDatabase(),
                        $relatedUser,
                        10001,
                        array(),
                        $RemoteAddr
                    );
                    $verifyEmailVeriCode->saveToDatabase();
                    $emailVericodeSender->sendVerificationCode($verifyEmailVeriCode,$locale);
                    $verifyEmailVeriCode->setSentMethod(SentMethod::EMAIL);
                    $verifyEmailVeriCode->saveToDatabase();
                }

            break;
            case 20003:
                $relatedUser->phone_verified = false;
                $newPhoneNum = $veriCode->getActionParam('new_phone');
                $newPhoneObj = empty($newPhoneNum) ? NULL : UserPhoneNumUtil::parsePhone($newPhoneNum,$country);
                $relatedUser->setPhoneNumberObj($newPhoneObj);
                $relatedUser->saveToDatabase();
                //Won't generate new verify phone vericode and send it cuz user can choose between SMS and phone calls.
            break;
            case 30001:
                //No action to execute, skip
            break;
            case 90001:
                //No action to execute, skip
            break;
            case 90002:
                //No action to execute, skip
            break;
        }
    }
    public static function verifyActionExecutable(VeriCode $veriCode, string $RemoteAddr, string &$errorMsg) : bool{
        $relatedUser = $veriCode->getUser();
        switch($veriCode->actionID){
            case 10001:
                if(empty($relatedUser->getEmail())){
                    $errorMsg = 'User Email Address is empty';
                    return false;
                }
            break;
            case 10002:
                if($relatedUser->getPhoneNumber() === NULL){
                    $errorMsg = 'User Phone Address is empty';
                    return false;
                }
            break;
            case 20002:
                if(empty($relatedUser->getEmail())){
                    $errorMsg = 'Original Email Address is empty';
                    return false;
                }
                /*
                if(empty($veriCode->getActionParam('new_email'))){
                    $errorMsg = 'New Email Address is empty';
                    return false;
                }
                */
            break;
            case 20003:
                if($relatedUser->getPhoneNumber() === NULL){
                    $errorMsg = 'Original Phone Number is empty';
                    return false;
                }
                /*
                if(empty($veriCode->getActionParam('new_phone'))){
                    $errorMsg = 'New Phone Number is empty';
                    return false;
                }
                */
            break;
        }
        if(!empty($veriCode->getActionParam('client_ip_addr')) && (!Setting::ALLOW_VERICODE_IP_CHANGE)){
            if(!Setting::ALLOW_VERICODE_IP_CHANGE){
                if($RemoteAddr !== $veriCode->getActionParam('client_ip_addr')){
                    $errorMsg = 'VeriCode IP not match';
                    return false;
                }
            }
        }
        return true;
    }
}