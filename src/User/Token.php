<?php
namespace InteractivePlus\PDK2020Core\User;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\Utils\DataUtil;
use MysqliDb;

class Token{
    public static function generateTokenValue(string $username) : string{
        return strtoupper(bin2hex(random_bytes(16)));
    }
    public static function verifyToken(string $token) : bool{
        return strlen($token) === 32;
    }
    private $_Database = NULL;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_refresh_token = NULL;
    private $_token = NULL;
    private $_uid = NULL;
    public $issueTime = 0;
    public $expireTime = 0;
    public $renewTime = 0;
    public $refresh_expire_time = 0;
    private $_client_addr = NULL;

    private $_createNewToken = false;

    private function __construct(){
        
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getRefreshTokenString() : string{
        return $this->_refresh_token;
    }

    public function changeRefreshTokenString(string $newRefreshToken) : void{
        if(!self::verifyToken($newRefreshToken)){
            throw new PDKException(30002,'Refresh Token format incorrrect',array('credential'=>'refresh_token'));
        }
        $newRefreshToken = strtoupper($newRefreshToken);
        if($this->_refresh_token == $newRefreshToken){
            return;
        }
        if(self::checkRefreshTokenExist($this->_Database, $newRefreshToken)){
            throw new PDKException(70006,'Refresh Token already exist');
        }
        if(!$this->_createNewToken){
            $this->_Database->where('token',$this->_token);
            $differenceArray = array(
                'refresh_token' => $newRefreshToken
            );
            $updateRst = $this->_Database->update('logged_infos',$differenceArray);
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

    public function getTokenString() : string{
        return $this->_token;
    }

    public function changeTokenString(string $newTokenString) : void{
        if(!self::verifyToken($newTokenString)){
            throw new PDKException(30002,'Token format incorrrect',array('credential'=>'token'));
        }
        $newTokenString = strtoupper($newTokenString);
        if($this->_token == $newTokenString){
            return;
        }
        if(self::checkTokenIDExist($this->_Database, $newTokenString)){
            throw new PDKException(70003,'Token already exist');
        }
        if(!$this->_createNewToken){
            $this->_Database->where('token',$this->_token);
            $differenceArray = array(
                'token' => $newTokenString
            );
            $updateRst = $this->_Database->update('logged_infos',$differenceArray);
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
            $this->_lastDataArray['token'] = $newTokenString;
        }
        $this->_token = $newTokenString;
    }

    public function getUID() : string{
        return $this->_uid;
    }

    public function getUser() : User{
        return User::fromUID($this->_Database, $this->_uid);
    }

    public function setUser(User $user) : void{
        $this->_uid = $user->getUID();
    }

    public function renew(int $availableDuration, int $refreshAvailableDuration) : void{
        $ctime = time();
        $this->expireTime = $ctime + $availableDuration;
        $this->renewTime = $ctime;
        $this->refresh_expire_time = $ctime + $refreshAvailableDuration;
    }

    public function getClientAddress() : string{
        return $this->_client_addr;
    }

    public function setClientAddress(string $addr) : void{
        $this->_client_addr = $addr;
    }

    public function readFromDataRow(array $dataRow) : void{
        $this->_refresh_token = $dataRow['refresh_token'];
        $this->_token = $dataRow['token'];
        $this->_uid = $dataRow['uid'];
        $this->issueTime = $dataRow['issue_time'];
        $this->expireTime = $dataRow['expire_time'];
        $this->renewTime = $dataRow['renew_time'];
        $this->refresh_expire_time = $dataRow['refresh_expire_time'];
        $this->_client_addr = $dataRow['client_addr'];
    }
    public function saveToDataArray() : array{
        $returnArr = array(
            'refresh_token' => $this->_refresh_token,
            'token' => $this->_token,
            'uid' => $this->_uid,
            'issue_time' => $this->issueTime,
            'expire_time' => $this->expireTime,
            'renew_time' => $this->renewTime,
            'refresh_expire_time' => $this->refresh_expire_time,
            'client_addr' => $this->_client_addr
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
        $this->_Database->where('token',$this->_token);
        $updateRst = $this->_Database->update('logged_infos',$differenceArray);
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
        $insertedID = $this->_Database->insert('logged_infos',$dataArray);
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
            $this->_Database->where('token',$this->_token);
            $updateRst = $this->_Database->delete('logged_infos');
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
        string $client_ip,
        string $customTokenID = NULL,
        string $customRefreshToken = NULL
    ) : Token{
        $actualToken = '';
        if(!empty($customTokenID)){
            if(self::verifyToken($customTokenID)){
                $actualToken = $customTokenID;
            }else{
                throw new PDKException(30002,'Token format incorrect',array('credential'=>'token'));
            }
        }else{
            $actualToken = self::generateTokenValue($user->getUsername());
        }

        $actualToken = strtoupper($actualToken);
        
        //check replication of tokens first
        if(self::checkTokenIDExist($Database,$actualToken)){
            if(!empty($customTokenID)){
                throw new PDKException(70003, 'Token already exist');
                return;
            }
            //regenerate actual token and return the new token.
            return self::createToken($Database,$user,$client_ip,$customTokenID,$customRefreshToken);
        }

        $actualRefreshToken = '';
        if(!empty($customRefreshToken)){
            if(self::verifyToken($customRefreshToken)){
                $actualRefreshToken = $customRefreshToken;
            }else{
                throw new PDKException(30002,'Refresh Token format incorrect',array('credential'=>'refresh_token'));
            }
        }else{
            $actualRefreshToken = self::generateTokenValue($user->getUsername());
        }

        $actualRefreshToken = strtoupper($actualRefreshToken);
        
        //check replication of tokens first
        if(self::checkRefreshTokenExist($Database,$actualRefreshToken)){
            if(!empty($customRefreshToken)){
                throw new PDKException(70006, 'Refresh Token already exist');
                return;
            }
            //regenerate actual token and return the new token.
            return self::createToken($Database,$user,$client_ip,$customTokenID,$customRefreshToken);
        }

        $returnObj = new Token();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_uid = $user->getUID();
        $returnObj->_token = $actualToken;
        $returnObj->_refresh_token = $actualRefreshToken;
        $returnObj->_client_addr = $client_ip;
        $returnObj->issueTime = $ctime;
        $returnObj->renewTime = $ctime;
        $returnObj->expireTime = $ctime + Setting::TOKEN_AVAILABLE_DURATION;
        $returnObj->refresh_expire_time = $ctime + Setting::REFRESH_TOKEN_AVAILABLE_DURATION;

        $returnObj->_createNewToken = true;
        return $returnObj;
    }

    public static function fromTokenID(MysqliDb $Database, string $token){
        if(!self::verifyToken($token)){
            throw new PDKException(30002,'Token format incorrrect',array('credential'=>'token'));
        }
        $token = strtoupper($token);
        $Database->where('token',$token);
        $dataRow = $Database->getOne('logged_infos');
        if(!$dataRow){
            throw new PDKException(70002,'Token non-existant');
        }
        $returnObj = new Token();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewToken = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function fromRefreshToken(MysqliDb $Database, string $refreshToken){
        if(!self::verifyToken($refreshToken)){
            throw new PDKException(30002,'Token format incorrrect',array('credential'=>'token'));
        }
        $refreshToken = strtoupper($refreshToken);
        $Database->where('refresh_token',$refreshToken);
        $dataRow = $Database->getOne('logged_infos');
        if(!$dataRow){
            throw new PDKException(70005,'Refresh Token non-existant');
        }
        $returnObj = new Token();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewToken = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function checkTokenIDExist(MysqliDb $Database, string $token) : bool{
        $token = strtoupper($token);
        $Database->where('token',$token);
        $count = $Database->getValue('logged_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function checkRefreshTokenExist(MysqliDb $Database, string $token) : bool{
        $token = strtoupper($token);
        $Database->where('refresh_token',$token);
        $count = $Database->getValue('logged_infos','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function clearTokenID(MysqliDb $Database,int $expireEarlierThan) : void{
        $Database->where('expire_time',$expireEarlierThan,'<');
        $updateRst = $Database->delete('logged_infos');
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
        $updateRst = $Database->delete('logged_infos');
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