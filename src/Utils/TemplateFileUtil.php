<?php
namespace InteractivePlus\PDK2020Core\Utils;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;
class TemplateFileUtil{
    public static function getEmailTemplateContent(int $actionID, string $language) : string{
        $path = PathUtil::getTemplatePath() . '/templates/email/' . $language . '/verification_' . $actionID . '.tpl';
        if(file_exists($path)){
            return file_get_contents($path);
        }else{
            if(Setting::DEBUG_MODE){
                throw new PDKException(51000,'Template File Non-existant');
            }
            return '';
        }
    }
    public static function getEmailTemplateTitle(int $actionID, string $language) : string{
        $path = PathUtil::getTemplatePath() . '/templates/email/' . $language . '/verification_' . $actionID . '.title';
        if(file_exists($path)){
            return file_get_contents($path);
        }else{
            if(Setting::DEBUG_MODE){
                throw new PDKException(51000,'Template File Non-existant');
            }
            return '';
        }
    }
    public static function getSMSTemplateContent(int $actionID, string $language) : string{
        $path = PathUtil::getTemplatePath() . '/templates/SMS/' . $language . '/verification_' . $actionID . '.tpl';
        if(file_exists($path)){
            return file_get_contents($path);
        }else{
            if(Setting::DEBUG_MODE){
                throw new PDKException(51000,'Template File Non-existant');
            }
            return '';
        }
    }
}