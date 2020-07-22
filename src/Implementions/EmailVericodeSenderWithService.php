<?php
namespace InteractivePlus\PDK2020Core\Implementions;

use Exception;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\Utils\IntlUtil;
use InteractivePlus\PDK2020Core\Utils\PathUtil;
use InteractivePlus\PDK2020Core\Utils\TemplateEngine;
use InteractivePlus\PDK2020Core\Utils\TemplateFileUtil;

class EmailVericodeSenderWithService implements \InteractivePlus\PDK2020Core\Interfaces\EmailVericodeSender{
    private $_serviceProvider = null;
    public $baseTemplateFolderPath = __DIR__ . '/../../' . 'templates/email/';
    public function __construct(\InteractivePlus\PDK2020Core\Interfaces\EmailServiceProvider $serviceProvider){
        if($serviceProvider === NULL){
            throw new Exception("Service Provider cannot be NULL");
        }
        $this->_serviceProvider = $serviceProvider;
    }
    public function getServiceProvider() : \InteractivePlus\PDK2020Core\Interfaces\EmailServiceProvider{
        return $this->_serviceProvider;
    }
    public function setServiceProvider(\InteractivePlus\PDK2020Core\Interfaces\EmailServiceProvider $provider) : void{
        if($provider === null){
            return;
        }
        $this->_serviceProvider = $provider;
    }

    public function sendVerificationCode(\InteractivePlus\PDK2020Core\VerificationCodes\VeriCode $verificationCode, string $LOCALE_OVERRIDE = NULL) : void{
        //TODO: Finish the switch actionID part + optimize exception flow
        //First verify whether we can actually send out that email
        if($this->_serviceProvider === NULL){
            throw new \Exception("Service Provider cannot be NULL");
        }
        $toEmail = $verificationCode->getUser()->getEmail();
        if(empty($toEmail)){
            throw new \Exception("Non-existant record for user email");
        }

        $relatedUser = $verificationCode->getUser();
        $language = empty($LOCALE_OVERRIDE) ? $verificationCode->getUser()->getLocale() : IntlUtil::fixLocale($LOCALE_OVERRIDE);

        //Set up variables for future use
        $generatedTitle = '';
        $generatedHTMLContent = '';
        
        //check which verification code to send.
        switch($verificationCode->actionID){
            //TODO: fill in title and email content using template engine.
            case 10001:
                $variableList = array(
                    'systemName' => IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_NAME),
                    'username' => $relatedUser->getUsername(),
                    'userDisplayName' => $relatedUser->getDisplayName(),
                    'userEmail' => $relatedUser->getEmail(),
                    'veriLink' => TemplateEngine::quickRenderPage(
                        IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_LINKS)['confirm_email_url'],
                        array('veri_code'=>$verificationCode->getVerificationCode())
                    )
                );
                $generatedTitle = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateTitle($verificationCode->actionID,$language),
                    $variableList
                );
                $generatedHTMLContent = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateContent($verificationCode->actionID,$language),
                    $variableList
                );
                break;
            case 20001:
                $variableList = array(
                    'systemName' => IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_NAME),
                    'username' => $relatedUser->getUsername(),
                    'userDisplayName' => $relatedUser->getDisplayName(),
                    'veriCode' => $verificationCode->getVerificationCode(),
                    'veriLink' => TemplateEngine::quickRenderPage(
                        IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_LINKS)['change_pwd_url'],
                        array('veri_code'=>$verificationCode->getVerificationCode())
                    )
                );
                $generatedTitle = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateTitle($verificationCode->actionID,$language),
                    $variableList
                );
                $generatedHTMLContent = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateContent($verificationCode->actionID,$language),
                    $variableList
                );
                break;
            case 20002:
                $variableList = array(
                    'systemName' => IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_NAME),
                    'username' => $relatedUser->getUsername(),
                    'userDisplayName' => $relatedUser->getDisplayName(),
                    'userEmail' => $relatedUser->getEmail(),
                    'veriCode' => $verificationCode->getVerificationCode(),
                    'veriLink' => TemplateEngine::quickRenderPage(
                        IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_LINKS)['confirm_email_change_url'],
                        array('veri_code'=>$verificationCode->getVerificationCode())
                    ),
                    'newEmail' => $verificationCode->getActionParam('new_email')
                );
                $generatedTitle = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateTitle($verificationCode->actionID,$language),
                    $variableList
                );
                $generatedHTMLContent = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateContent($verificationCode->actionID,$language),
                    $variableList
                );
                break;
            case 20003:
                $variableList = array(
                    'systemName' => IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_NAME),
                    'username' => $relatedUser->getUsername(),
                    'userDisplayName' => $relatedUser->getDisplayName(),
                    'userPhone' => $relatedUser->getPhoneNumberStr(),
                    'veriCode' => $verificationCode->getVerificationCode(),
                    'veriLink' => TemplateEngine::quickRenderPage(
                        IntlUtil::getMultiLangVal($language,Setting::USER_SYSTEM_LINKS)['confirm_phone_change_url'],
                        array('veri_code'=>$verificationCode->getVerificationCode())
                    ),
                    'newPhone' => $verificationCode->getActionParam('new_phone')
                );
                $generatedTitle = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateTitle($verificationCode->actionID,$language),
                    $variableList
                );
                $generatedHTMLContent = TemplateEngine::quickRenderPage(
                    TemplateFileUtil::getEmailTemplateContent($verificationCode->actionID,$language),
                    $variableList
                );
                break;
            default:
            //TODO: Optimize this Exception method
            throw new \Exception("No appropriate template for actionID");
        }
        //Before sending, clear up Serivce Provider
        $this->getServiceProvider()->clear();

        //Let's send!
        $this->getServiceProvider()->setSubject($generatedTitle);
        $this->getServiceProvider()->setBody($generatedHTMLContent);
        $this->getServiceProvider()->addToAccount($toEmail,$verificationCode->getUser()->getDisplayName());
        $sendResult = $this->getServiceProvider()->send();
        if(!$sendResult){
            throw new \Exception('Failed to send email');
        }
    }
}