<?php
namespace InteractivePlus\PDK2020Core\OAuth;

use InteractivePlus\PDK2020Core\Apps\AppEntity;
use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Formats\OAuthFormat;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\User\User;
use InteractivePlus\PDK2020Core\Utils\DataUtil;
use MysqliDb;

class OAuthTokenPair{
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_refresh_token = NULL;
    private $_access_token = NULL;
    private $_uid = 0;
    private $_appuid = 0;
    public $issueTime = 0;
    public $access_expire_time = 0;
    public $refresh_expire_time = 0;
    private $_scopeArr = array();

    private $_createNewToken = false;

    private function __construct(){
        
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getRefreshToken() : string{
        return $this->_refresh_token;
    }

    public function changeRefreshToken(string $newRefreshToken) : void{
        if(!OAuthFormat::verifyRefreshToken($newRefreshToken)){
            throw new PDKException(90100,'OAuth Refresh Token format incorrrect',array('credential'=>'refresh_token'));
        }
        $newRefreshToken = strtolower($newRefreshToken);
        if($this->_refresh_token == $newRefreshToken){
            return;
        }
        if(self::checkRefreshTokenExist($this->_Database, $newRefreshToken)){
            throw new PDKException(90005,'OAuth Refresh Token already exist');
        }
        if(!$this->_createNewToken){
            $this->_Database->where('access_token',$this->_access_token);
            $differenceArray = array(
                'refresh_token' => $newRefreshToken
            );
            $updateRst = $this->_Database->update('oauth_tokens',$differenceArray);
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
            $this->_lastDataArray['refresh_token'] = $newRefreshToken;
        }
        $this->_refresh_token = $newRefreshToken;
    }

    public function getAccessToken() : string{
        return $this->_access_token;
    }

    public function changeAccessToken(string $newToken) : void{
        if(!OAuthFormat::verifyAccessToken($newToken)){
            throw new PDKException(90100,'OAuth Access Token format incorrrect',array('credential'=>'access_token'));
        }
        $newToken = strtolower($newToken);
        if($this->_access_token == $newToken){
            return;
        }
        if(self::checkTokenIDExist($this->_Database, $newToken)){
            throw new PDKException(90003,'OAuth Access Token already exist');
        }
        if(!$this->_createNewToken){
            $this->_Database->where('access_token',$this->_access_token);
            $differenceArray = array(
                'access_token' => $newToken
            );
            $updateRst = $this->_Database->update('oauth_tokens',$differenceArray);
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
            $this->_lastDataArray['access_token'] = $newToken;
        }
        $this->_access_token = $newToken;
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

    public function getAPPUID() : int{
        return $this->_appuid;
    }

    public function getAPP() : AppEntity{
        return AppEntity::fromAPPUID($this->_Database,$this->_appuid);
    }

    public function setAPP(AppEntity $app) : void{
        $this->_appuid = $app->getAppUID();
    }

    public function renew(int $accessDuration, int $refreshDuration) : void{
        $ctime = time();
        $this->access_expire_time = $ctime + $accessDuration;
        $this->refresh_expire_time = $ctime + $refreshDuration;
    }

    public function getScopes() : array{
        return $this->_scopeArr;
    }

    public function setScopes(array $scopes){
        $this->_scopeArr = $scopes;
    }

    public function isScope(string $scope) : bool{
        return in_array($scope,$this->_scopeArr,true);
    }

    public function addScope(string $scope) : void{
        if(!$this->isScope($scope)){
            $this->_scopeArr[] = $scope;
        }
    }

    public function delScope(string $scope) : void{
        $position = array_search($scope,$this->_scopeArr,true);
        if($position !== false){
            unset($this->_scopeArr[$position]);
        }
    }

    public function readFromDataRow(array $dataRow) : void{
        $this->_refresh_token = $dataRow['refresh_token'];
        $this->_access_token = $dataRow['access_token'];
        $this->_appuid = $dataRow['appuid'];
        $this->_uid = $dataRow['uid'];
        $this->issueTime = $dataRow['issue_time'];
        $this->access_expire_time = $dataRow['access_expire_time'];
        $this->refresh_expire_time = $dataRow['refresh_expire_time'];
        $this->_scopeArr = empty($dataRow['scope']) ? array() : explode(' ',$dataRow['scope']);
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            'refresh_token' => $this->_refresh_token,
            'access_token' => $this->_access_token,
            'appuid' => $this->_appuid,
            'uid' => $this->_uid,
            'issue_time' => $this->issueTime,
            'access_expire_time' => $this->access_expire_time,
            'refresh_expire_time' => $this->refresh_expire_time,
            'scope' => implode(' ',$this->_scopeArr)
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
        $this->_Database->where('access_token',$this->_access_token);
        $updateRst = $this->_Database->update('oauth_tokens',$differenceArray);
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
        $insertedID = $this->_Database->insert('oauth_tokens',$dataArray);
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
        if($this->_createNewToken){
            $this->insertToDatabase();
            $this->_createNewToken = false;
        }else{
            $this->updateToDatabase();
        }
    }

    public function delete() : void{
        if($this->_createNewToken){
            $this->_createNewToken = false;
            return;
        }
        //Delete existing record row of this token
        {
            $this->_Database->where('access_token',$this->_access_token);
            $updateRst = $this->_Database->delete('oauth_tokens');
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
        //successfully deleted, now need to unset the Token variable
    }
    
    public static function createToken(
        MysqliDb $Database,
        User $user,
        AppEntity $appEntity,
        array $scopes,
        string $customAccessToken = NULL,
        string $customRefreshToken = NULL
    ) : OAuthTokenPair{
        $actualToken = '';
        if(!empty($customAccessToken)){
            if(OAuthFormat::verifyAccessToken($customAccessToken)){
                $actualToken = $customAccessToken;
            }else{
                throw new PDKException(90100,'OAuth Access Token format incorrect',array('credential'=>'access_token'));
            }
        }else{
            $actualToken = OAuthFormat::generateAccessToken();
        }

        $actualToken = strtolower($actualToken);
        
        //check replication of tokens first
        if(self::checkTokenIDExist($Database,$actualToken)){
            if(!empty($customAccessToken)){
                throw new PDKException(90003, 'OAuth Access Token already exist');
                return;
            }
            //regenerate actual token and return the new token.
            return self::createToken($Database,$user,$appEntity,$scopes,$customAccessToken,$customRefreshToken);
        }

        $actualRefreshToken = '';
        if(!empty($customRefreshToken)){
            if(OAuthFormat::verifyRefreshToken($customRefreshToken)){
                $actualRefreshToken = $customRefreshToken;
            }else{
                throw new PDKException(90100,'OAuth Refresh Token format incorrect',array('credential'=>'refresh_token'));
            }
        }else{
            $actualRefreshToken = OAuthFormat::generateRefreshToken();
        }

        $actualRefreshToken = strtolower($actualRefreshToken);
        
        //check replication of tokens first
        if(self::checkRefreshTokenExist($Database,$actualRefreshToken)){
            if(!empty($customRefreshToken)){
                throw new PDKException(70006, 'Refresh Token already exist');
                return;
            }
            //regenerate actual token and return the new token.
            return self::createToken($Database,$user,$appEntity,$scopes,$customAccessToken,$customRefreshToken);
        }

        $returnObj = new OAuthTokenPair();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_access_token = $actualToken;
        $returnObj->_refresh_token = $actualRefreshToken;
        $returnObj->_uid = $user->getUID();
        $returnObj->_appuid = $appEntity->getAppUID();
        $returnObj->issueTime = $ctime;
        $returnObj->access_expire_time = $ctime + Setting::OAUTH_ACCESS_TOKEN_AVAILABLE_DURATION;
        $returnObj->refresh_expire_time = $ctime + Setting::OAUTH_REFRESH_TOKEN_AVAILABLE_DURATION;
        $returnObj->_scopeArr = $scopes;

        $returnObj->_createNewToken = true;
        return $returnObj;
    }

    public static function fromTokenID(MysqliDb $Database, string $token){
        if(!OAuthFormat::verifyAccessToken($token)){
            throw new PDKException(90100,'OAuth Access Token format incorrrect',array('credential'=>'access_token'));
        }
        $token = strtolower($token);
        $Database->where('access_token',$token);
        $dataRow = $Database->getOne('oauth_tokens');
        if(!$dataRow){
            throw new PDKException(90004,'OAuth Access Token non-existant');
        }
        $returnObj = new OAuthTokenPair();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewToken = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function fromRefreshToken(MysqliDb $Database, string $refreshToken){
        if(!OAuthFormat::verifyRefreshToken($refreshToken)){
            throw new PDKException(90100,'OAuth Refresh Token format incorrrect',array('credential'=>'refresh_token'));
        }
        $refreshToken = strtolower($refreshToken);
        $Database->where('refresh_token',$refreshToken);
        $dataRow = $Database->getOne('oauth_tokens');
        if(!$dataRow){
            throw new PDKException(90006,'OAuth Refresh Token non-existant');
        }
        $returnObj = new OAuthTokenPair();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewToken = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function checkTokenIDExist(MysqliDb $Database, string $token) : bool{
        $token = strtolower($token);
        $Database->where('access_token',$token);
        $count = $Database->getValue('oauth_tokens','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkRefreshTokenExist(MysqliDb $Database, string $token) : bool{
        $token = strtolower($token);
        $Database->where('refresh_token',$token);
        $count = $Database->getValue('oauth_tokens','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function clearTokenID(MysqliDb $Database,int $expireEarlierThan) : void{
        $Database->where('access_expire_time',$expireEarlierThan,'<');
        $updateRst = $Database->delete('oauth_tokens');
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

    public static function clearTokenWithRefreshExpire(MysqliDb $Database,int $refreshExpireEarlierThan) : void{
        $Database->where('refresh_expire_time',$refreshExpireEarlierThan,'<');
        $updateRst = $Database->delete('oauth_tokens');
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
        $updateRst = $Database->delete('oauth_tokens');
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
        $updateRst = $Database->delete('oauth_tokens');
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