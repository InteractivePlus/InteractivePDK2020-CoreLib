<?php
namespace InteractivePlus\PDK2020Core\OAuth;
class CodeChallengeType{
    const S256 = 2;
    const PLAIN = 1;
    public static function isCodeChallengeType(int $type) : bool{
        switch($type){
            case self::S256:
            case self::PLAIN:
                return true;
        }
        return false;
    }
    public static function fixCodeChallengeType(int $type) : int{
        if(self::isCodeChallengeType($type)){
            return $type;
        }else{
            return self::S256;
        }
    }
}