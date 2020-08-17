<?php
namespace InteractivePlus\PDK2020Core\Logs;

use Psr\Log\LogLevel as PSRLogLevel;

class LogLevel{
    const EMERGENCY = 8;
    const ALERT = 7;
    const CRITICAL = 6;
    const ERROR = 5;
    const WARNING = 4;
    const NOTICE = 3;
    const INFO = 2;
    const DEBUG = 1;
    public static function fromPSRLogLevel(string $LogLevel) : int{
        switch($LogLevel){
            case PSRLogLevel::EMERGENCY:
                return self::EMERGENCY;
            break;
            case PSRLogLevel::ALERT:
                return self::ALERT;
            break;
            case PSRLogLevel::CRITICAL:
                return self::CRITICAL;
            break;
            case PSRLogLevel::ERROR:
                return self::ERROR;
            break;
            case PSRLogLevel::WARNING:
                return self::WARNING;
            break;
            case PSRLogLevel::NOTICE:
                return self::NOTICE;
            break;
            case PSRLogLevel::INFO:
                return self::INFO;
            break;
            case PSRLogLevel::DEBUG:
                return self::DEBUG;
            break;
            default:
                return self::INFO;
        }
    }
    public static function toPSRLogLevel(int $LogLevel) : string{
        switch($LogLevel){
            case self::EMERGENCY:
                return PSRLogLevel::EMERGENCY;
            break;
            case self::ALERT:
                return PSRLogLevel::ALERT;
            break;
            case self::CRITICAL:
                return PSRLogLevel::CRITICAL;
            break;
            case self::ERROR:
                return PSRLogLevel::ERROR;
            break;
            case self::WARNING:
                return PSRLogLevel::WARNING;
            break;
            case self::NOTICE:
                return PSRLogLevel::NOTICE;
            break;
            case self::INFO:
                return PSRLogLevel::INFO;
            break;
            case self::DEBUG:
                return PSRLogLevel::DEBUG;
            break;
            default:
                return PSRLogLevel::INFO;
        }
    }
    public static function isLogLevel(int $logLevel) : bool{
        return ($logLevel >= 1 && $logLevel <= 8);
    }
    public static function fixLogLevel(int $logLevel) : int{
        if(!self::isLogLevel($logLevel)){
            return self::INFO;
        }
        return $logLevel;
    }
}