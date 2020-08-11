<?php
namespace InteractivePlus\PDK2020Core\OAuth;

use Exception;
use InteractivePlus\PDK2020Core\Apps\AppEntity;
use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Formats\APPFormat;
use InteractivePlus\PDK2020Core\Formats\OAuthFormat;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\User\User;
use InteractivePlus\PDK2020Core\Utils\DataUtil;
use MysqliDb;

class AuthCode{
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_authorization_code = NULL;
    private $_appuid = 0;
    private $_uid = 0;
    private $_redirect_uri = NULL;
    public int $issue_time;
    public int $expire_time;
    private array $_scope_array = array();
    private string $_code_challenge = NULL;
    private int $_code_challenge_type = CodeChallengeType::S256;

    private $_createNewAuthCode = false;

    private function __construct(){
        
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getAuthorizationCode() : string{
        return $this->_authorization_code;
    }

    public function changeAuthorizationCode(string $newCode) : void{
        if(!OAuthFormat::verifyAuthCode($newCode)){
            throw new PDKException(90100,'Auth Code Format incorrect',array('credential'=>'authorization_code'));
        }
        $newCode = strtolower($newCode);
        if(self::checkAuthCodeExist($this->_Database,$newCode)){
            throw new PDKException(90001,'OAuth Authorization Code already exist');
        }
        if(!$this->_createNewAuthCode){
            $dataArr = array(
                'authorization_code' => $newCode
            );
            $this->_Database->where('authorization_code',$this->_authorization_code);
            $updateRst = $this->_Database->update('oauth_authorization_codes',$dataArr);
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    __CLASS__ . ' update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
            $this->_lastDataArray['authorization_code'] = $newCode;
        }
        $this->_authorization_code = $newCode;
    }

    public function getAPPUID() : int{
        return $this->_appuid;
    }

    public function getAPP() : AppEntity{
        return AppEntity::fromAPPUID($this->_Database, $this->_appuid);
    }

    public function setAPP(AppEntity $app) : void{
        $this->_appuid = $app->getAppUID();
    }

    public function getUserUID() : int{
        return $this->_uid;
    }

    public function getUser() : User{
        return User::fromUID($this->_Database,$this->_uid);
    }

    public function setUser(User $user) : void{
        $this->_uid = $user->getUID();
    }

    public function getRedirectURI() : string{
        return $this->_redirect_uri;
    }

    public function setRedirectURI(string $uri) : void{
        if(!empty($uri) && strlen($uri) > 500){
            throw new PDKException(30002,'Redirect URI format incorrect',array('credential'=>'redirect_uri'));
        }
        $this->_redirect_uri = $uri;
    }

    public function getScopes() : array{
        return $this->_scope_array;
    }

    public function setScopes(array $scopes){
        $this->_scope_array = $scopes;
    }

    public function isScope(string $scope) : bool{
        return in_array($scope,$this->_scope_array,true);
    }

    public function addScope(string $scope) : void{
        if(!$this->isScope($scope)){
            $this->_scope_array[] = $scope;
        }
    }

    public function delScope(string $scope) : void{
        $position = array_search($scope,$this->_scope_array,true);
        if($position !== false){
            unset($this->_scope_array[$position]);
        }
    }

    public function getCodeChallenge() : string{
        return $this->_code_challenge;
    }

    public function getCodeChallengeType() : int{
        return $this->_code_challenge_type;
    }

    public function setCodeChallenge(int $type, string $encodedChallenge){
        $type = CodeChallengeType::fixCodeChallengeType($type);
        if($type === CodeChallengeType::S256){
            $encodedChallenge = strtoupper($encodedChallenge);
        }
        $this->_code_challenge = $encodedChallenge;
        $this->_code_challenge_type = $type;
    }

    public function readFromDataRow(array $dataRow) : void{
        $this->_authorization_code = $dataRow['authorization_code'];
        $this->_appuid = $dataRow['appuid'];
        $this->_uid = $dataRow['uid'];
        $this->_redirect_uri = $dataRow['redirect_uri'];
        $this->issue_time = $dataRow['issue_time'];
        $this->expire_time = $dataRow['expire_time'];
        $this->_scope_array = empty($dataRow['scope']) ? array() : explode(' ', $dataRow['scope']);
        $this->_code_challenge = $dataRow['code_challenge'];
        $this->_code_challenge_type = $dataRow['challenge_type'];
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            'authorization_code' => $this->_authorization_code,
            'appuid' => $this->_appuid,
            'uid' => $this->_uid,
            'redirect_uri' => $this->_redirect_uri,
            'issue_time' => $this->issue_time,
            'expire_time' => $this->expire_time,
            'scope' => implode(' ',$this->_scope_array),
            'code_challenge' => $this->_code_challenge,
            'challenge_type' => $this->_code_challenge_type
        );
        return $returnArr;
    }
    protected function updateToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . ' class');
        }
        
        $newDataArray = $this->saveToDataArray();
        $differenceArray = array();
        if(empty($this->_lastDataArray)){
            $differenceArray = $newDataArray;
        }else{
            $oldDataArray = $this->_lastDataArray;
            $differenceArray = DataUtil::compareDataArrayDifference($newDataArray,$oldDataArray);
        }
        $this->_Database->where('authorization_code',$this->_authorization_code);
        $updateRst = $this->_Database->update('oauth_authorization_codes',$differenceArray);
        if(!$updateRst){
            throw new PDKException(
                50007,
                __CLASS__ . ' update error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
        $this->_dataTime = time();
        $this->_lastDataArray = $newDataArray;
    }
    protected function insertToDatabase() : void{
        if($this->_Database === NULL){
            throw new PDKException(50006,'No database connection stored in ' . __CLASS__ . ' class');
        }
        $dataArray = $this->saveToDataArray();
        $insertedID = $this->_Database->insert('oauth_authorization_codes',$dataArray);
        if(!$insertedID){
            throw new PDKException(
                50007,
                __CLASS__ . ' insert error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
        $this->_dataTime = time();
        $this->_lastDataArray = $dataArray;
    }
    public function saveToDatabase() : void{
        if($this->_createNewAuthCode){
            $this->insertToDatabase();
            $this->_createNewAuthCode = false;
        }else{
            $this->updateToDatabase();
        }
    }

    public function delete() : void{
        if($this->_createNewAuthCode){
            $this->_createNewAuthCode = false;
            return;
        }
        //Delete existing record row of this auth code
        {
            $this->_Database->where('authorization_code',$this->_authorization_code);
            $updateRst = $this->_Database->delete('oauth_authorization_codes');
            if(!$updateRst){
                throw new PDKException(
                    50007,
                    __CLASS__ . ' update error',
                    array(
                        'errNo'=>$this->_Database->getLastErrno(),
                        'errMsg'=>$this->_Database->getLastError()
                    )
                );
            }
        }
        //successfully deleted, now need to unset the AuthCode variable
    }
    
    public static function createAuthCode(
        MysqliDb $Database,
        AppEntity $appEntity,
        User $user,
        array $scopes,
        ?string $codeChallenge = NULL,
        int $codeChallengeType = CodeChallengeType::S256,
        ?string $redirectURI = NULL,
        ?string $customAuthCode = NULL
    ) : AuthCode{
        $actualAuthCode = '';
        if(!empty($customAuthCode)){
            if(OAuthFormat::verifyAccessToken($customAuthCode)){
                $actualAuthCode = $customAuthCode;
            }else{
                throw new PDKException(90100,'OAuth Authorization Code format incorrect',array('credential'=>'authorization_code'));
            }
        }else{
            $actualAuthCode = OAuthFormat::generateAccessToken();
        }

        $actualAuthCode = strtolower($actualAuthCode);
        
        //check replication of auth codes first
        if(self::checkAuthCodeExist($Database,$actualAuthCode)){
            if(!empty($actualAuthCode)){
                throw new PDKException(90001, 'OAuth Authorization Code already exist');
                return;
            }
            //regenerate actual auth code and return the new auth code.
            return self::createAuthCode(
                $Database,
                $appEntity,
                $user,
                $scopes,
                $codeChallenge,
                $codeChallengeType,
                $redirectURI,
                NULL
            );
        }

        $actualRedirectURI = empty($redirectURI) ? $appEntity->getRedirectURI() : $redirectURI;
        $codeChallengeType = CodeChallengeType::fixCodeChallengeType($codeChallengeType);
        if($codeChallengeType === CodeChallengeType::S256){
            $codeChallenge = strtoupper($codeChallenge);
        }


        $returnObj = new AuthCode();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_authorization_code = $actualAuthCode;
        $returnObj->_appuid = $appEntity->getAppUID();
        $returnObj->_uid = $user->getUID();
        $returnObj->_redirect_uri = $actualRedirectURI;
        $returnObj->issue_time = $ctime;
        $returnObj->expire_time = $ctime + Setting::OAUTH_AUTH_CODE_AVAILABLE_DURATION;
        $returnObj->_scope_array = $scopes;
        $returnObj->_code_challenge = $codeChallenge;
        $returnObj->_code_challenge_type = $codeChallengeType;

        $returnObj->_createNewAuthCode = true;
        return $returnObj;
    }

    public static function fromAuthorizationCode(MysqliDb $Database, string $authCode){
        if(!OAuthFormat::verifyAccessToken($authCode)){
            throw new PDKException(90100,'OAuth Auth Code format incorrrect',array('credential'=>'authorization_code'));
        }
        $authCode = strtolower($authCode);
        $Database->where('authorization_code',$authCode);
        $dataRow = $Database->getOne('oauth_authorization_codes');
        if(!$dataRow){
            throw new PDKException(90002,'OAuth Authorization Code non-existant');
        }
        $returnObj = new AuthCode();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewAuthCode = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function checkAuthCodeExist(MysqliDb $Database, string $code) : bool{
        $code = strtolower($code);
        $Database->where('authorization_code',$code);
        $count = $Database->getValue('oauth_authorization_codes','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function clearAuthorizationCodes(MysqliDb $Database,int $expireEarlierThan) : void{
        $Database->where('expire_time',$expireEarlierThan,'<');
        $updateRst = $Database->delete('oauth_authorization_codes');
        if(!$updateRst){
            throw new PDKException(
                50007,
                __CLASS__ . ' update error',
                array(
                    'errNo'=>$Database->getLastErrno(),
                    'errMsg'=>$Database->getLastError()
                )
            );
        }
    }

    public static function deleteUser(MysqliDb $Database, int $uid) : void{
        $Database->where('uid',$uid);
        $updateRst = $Database->delete('oauth_authorization_codes');
        if(!$updateRst){
            throw new PDKException(
                50007,
                __CLASS__ . ' update error',
                array(
                    'errNo'=>$Database->getLastErrno(),
                    'errMsg'=>$Database->getLastError()
                )
            );
        }
    }

    public static function deleteAPP(MysqliDb $Database, int $appuid) : void{
        $Database->where('appuid',$appuid);
        $updateRst = $Database->delete('oauth_authorization_codes');
        if(!$updateRst){
            throw new PDKException(
                50007,
                __CLASS__ . ' update error',
                array(
                    'errNo'=>$Database->getLastErrno(),
                    'errMsg'=>$Database->getLastError()
                )
            );
        }
    }
}