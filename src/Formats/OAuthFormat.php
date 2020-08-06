<?php
namespace InteractivePlus\PDK2020Core\Formats;
class OAuthFormat{
    public static function generateAuthCode() : string{
        return strtolower(bin2hex(random_bytes(20)));
    }
    public static function encodeCodeChallengeS256(string $originalStr) : string{
        return strtoupper(hash('sha256',$originalStr));
    }
    public static function generateAccessToken() : string{
        return strtolower(bin2hex(random_bytes(20)));
    }
    public static function generateRefreshToken() : string{
        return strtolower(bin2hex(random_bytes(20)));
    }
    public static function verifyAuthCode(string $authCode) : bool{
        return !empty($authCode) && strlen($authCode) === 40;
    }
    public static function verifyAccessToken(string $token) : bool{
        return !empty($token) && strlen($token) === 40;
    }
    public static function verifyRefreshToken(string $token) : bool{
        return !empty($token) && strlen($token) === 40;
    }
}