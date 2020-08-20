<?php
namespace InteractivePlus\PDK2020Core\VerificationCodes;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Settings\Setting;
use InteractivePlus\PDK2020Core\User\User;
use InteractivePlus\PDK2020Core\Utils\MultipleQueryResult;
use MysqliDb;

class VeriCode{
    public static function generateVerificationCode(string $username) : string{
        return strtoupper(bin2hex(random_bytes(16)));
    }
    public static function verifyCode(string $code) : bool{
        return strlen($code) === 32;
    }

    protected $_Database;
    private $_dataTime = 0;
    private $_lastDataArray = NULL;

    private $_veriCode = NULL;
    private $_uid = NULL;
    public $actionID = 0;
    private $_action_param_array = array();
    private $_sentMethod = SentMethod::NOTSENT;
    public $issueTime = 0;
    public $expireTime = 0;
    private $_used_stage = VeriCodeUsedStage::INVALID;
    public $triggerClientAddr = NULL;

    private $_createNewCode = false;

    private function __construct(){

    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }

    public function getLastFetchDataTime() : int{
        return $this->_dataTime;
    }

    public function getVerificationCode() : string{
        return $this->_veriCode;
    }

    public function changeVerificationCode(string $newCode) : void{
        if(!self::verifyCode($newCode)){
            throw new PDKException(30002,'Verification Code not formatted',array('credential'=>'veri_code'));
        }
        $newCode = strtoupper($newCode);
        if($newCode == $this->_veriCode){
            return;
        }
        if(self::checkVeriCodeExist($this->_Database,$newCode)){
            throw new PDKException(80003, 'Verification Code already exist');
        }
        if(!$this->_createNewCode){
            //Update Database
            $this->_Database->where('veri_code',$this->_veriCode);
            $differenceArray = array(
                'veri_code' => $newCode
            );
            $updateRst = $this->_Database->update('verification_codes',$differenceArray);
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
            $this->_lastDataArray['veri_code'] = $newCode;
        }
        $this->_veriCode = $newCode;
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

    public function getActionParams() : array{
        return $this->_action_param_array;
    }

    public function setActionParams(array $params) : void{
        $this->_action_param_array = $params;
    }

    public function getActionParam(string $key){
        return $this->_action_param_array[$key];
    }

    public function setActionParam(string $key, $value) : void{
        if($value !== NULL){
            $this->_action_param_array[$key] = $value;
        }else{
            unset($this->_action_param_array[$key]);
        }
    }

    public function getSentMethod() : int{
        return $this->_sentMethod;
    }

    public function setSentMethod(int $method) : void{
        $this->_sentMethod = SentMethod::fixMethod($method);
    }

    public function getUsedStage() : int{
        return $this->_used_stage;
    }

    public function setUsedStage(int $stage) : void{
        $this->_used_stage = VeriCodeUsedStage::fixStage($stage);
    }

    public function readFromDataRow(array $DataRow) : void{
        $this->_veriCode = $DataRow['veri_code'];
        $this->_uid = $DataRow['uid'];
        $this->actionID = $DataRow['action_id'];
        $this->_action_param_array = empty($DataRow['action_param']) ? array() : json_decode(gzuncompress($DataRow['action_param']),true);
        $this->_sentMethod = $DataRow['sent_method'];
        $this->issueTime = $DataRow['issue_time'];
        $this->expireTime = $DataRow['expire_time'];
        $this->_used_stage = $DataRow['used_stage'];
        $this->triggerClientAddr = $DataRow['trigger_client_ip'];
    }

    public function saveToDataArray() : array{
        $savedArray = array(
            'veri_code' => $this->_veriCode,
            'uid' => $this->_uid,
            'action_id' => $this->actionID,
            'action_param' => empty($this->_action_param_array) ? NULL : gzcompress(json_encode($this->_action_param_array)),
            'sent_method' => $this->_sentMethod,
            'issue_time' => $this->issueTime,
            'expire_time' => $this->expireTime,
            'used_stage' => $this->_used_stage,
            'trigger_client_ip' => $this->triggerClientAddr
        );
        return $savedArray;
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
            $differenceArray = \InteractivePlus\PDK2020Core\Utils\DataUtil::compareDataArrayDifference($newDataArray,$oldDataArray);
        }
        if(empty($differenceArray)){
            return;
        }
        $this->_Database->where('code',$this->_veriCode);
        $updateRst = $this->_Database->update('verification_codes',$differenceArray);
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
        $insertedID = $this->_Database->insert('verification_codes',$dataArray);
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
        if($this->_createNewCode){
            $this->insertToDatabase();
            $this->_createNewCode = false;
        }else{
            $this->updateToDatabase();
        }
    }

    public function delete() : void{
        if($this->_createNewCode){
            $this->_createNewCode = false;
            return;
        }
        //Delete existing record row of this Verification Code
        {
            $this->_Database->where('veri_code',$this->_veriCode);
            $updateRst = $this->_Database->delete('verification_codes');
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
        //successfully deleted, now need to unset the VeriCode variable
    }

    public static function createNewCode(
        MysqliDb $Database, 
        User $user,
        int $actionId,
        array $actionParam,
        string $client_ip,
        string $customVeriCode = NULL
    ) : VeriCode{
        $actualCode = '';
        if(!empty($customVeriCode)){
            if(self::verifyCode($customVeriCode)){
                $actualCode = $customVeriCode;
            }else{
                throw new PDKException(30002,'Verification Code format incorrect',array('credential'=>'veri_code'));
            }
        }else{
            $actualCode = self::generateVerificationCode($user->getUsername());
        }

        $actualCode = strtoupper($actualCode);
        
        //check replication of VeriCodes first
        if(self::checkVeriCodeExist($Database,$actualCode)){
            if(!empty($customVeriCode)){
                throw new PDKException(80003, 'Verification Code already exist');
            }
            //regenerate actual VeriCode and return the new VeriCode.
            return self::createNewCode($Database,$user,$actionId,$actionParam,$client_ip,$customVeriCode);
        }

        $returnObj = new VeriCode();
        
        $ctime = time();

        $returnObj->_Database = $Database;
        $returnObj->_dataTime = $ctime;
        $returnObj->_lastDataArray = array();

        $returnObj->_veriCode = $actualCode;
        $returnObj->_uid = $user->getUID();
        $returnObj->actionID = $actionId;
        $returnObj->_action_param_array = $actionParam;
        $returnObj->_sentMethod = SentMethod::NOTSENT;
        $returnObj->issueTime = $ctime;
        $returnObj->expireTime = $ctime + Setting::VERIFICATION_CODE_AVAILABLE_DURATION;
        $returnObj->_used_stage = VeriCodeUsedStage::VALID;
        $returnObj->triggerClientAddr = $client_ip;

        $returnObj->_createNewCode = true;
        return $returnObj;
    }

    public static function fromVeriCode(MysqliDb $Database, string $code) : VeriCode{
        if(!self::verifyCode($code)){
            throw new PDKException(30002,'Verification Code format incorrect',array('credential'=>'veri_code'));
        }
        $code = strtoupper($code);
        $Database->where('veri_code',$code);
        $dataRow = $Database->getOne('verification_codes');
        if(!$dataRow){
            throw new PDKException(80002,'Verification Code non-existant');
        }
        $returnObj = new VeriCode();
        $returnObj->_Database = $Database;
        $returnObj->_dataTime = time();
        $returnObj->_lastDataArray = $dataRow;
        $returnObj->_createNewCode = false;
        $returnObj->readFromDataRow($dataRow);
        return $returnObj;
    }

    public static function getSearchResults(MysqliDb $Database, int $action_id = -1, int $expireLow = -1, int $expireHigh = -1, int $uid = -1, int $numLimit = -1, int $offset = 0, string $CONNECT_OPERATOR = 'AND') : MultipleQueryResult{
        $returnArr = array();
        if($action_id != -1){
            $Database->where('action_id',$action_id,'=',$CONNECT_OPERATOR);
        }
        if($expireLow != -1){
            $Database->where('expire_time',$expireLow,'>=', $CONNECT_OPERATOR);
        }
        if($expireHigh != -1){
            $Database->where('expire_time',$expireHigh,'<=', $CONNECT_OPERATOR);
        }
        if($uid != -1){
            $Database->where('uid',$uid,'=', $CONNECT_OPERATOR);
        }
        $limitParam = NULL;
        if($numLimit !== -1 && $offset === 0){
            $limitParam = $numLimit;
        }else if($numLimit !== -1 && $offset !== 0){
            $limitParam = array($offset,$numLimit);
        }
        $resultArray = $Database->withTotalCount()->get('verification_codes',$limitParam);
        if($Database->count <= 0){
            return new MultipleQueryResult($offset,0,$Database->totalCount,NULL);
        }

        $dataTime = time();
        
        $veriCodeObjArr = array();
        foreach($resultArray as $singleRow){
            $veriCodeObj = new VeriCode();
            $veriCodeObj->_Database = $Database;
            $veriCodeObj->_dataTime = $dataTime;
            $veriCodeObj->_lastDataArray = $singleRow;
            $veriCodeObj->_createNewCode = false;
            $veriCodeObj->readFromDataRow($singleRow);
            $veriCodeObjArr[] = $veriCodeObj;
        }
        return new MultipleQueryResult($offset,$Database->count,$Database->totalCount,$veriCodeObjArr);
    }

    public static function checkVeriCodeExist(MysqliDb $Database, string $veriCode) : bool{
        $veriCode = strtoupper($veriCode);
        $Database->where('veri_code',$veriCode);
        $count = $Database->getValue('verification_codes','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    public static function deleteUser(MysqliDb $Database, int $uid) : void{
        $Database->where('uid',$uid);
        $updateRst = $Database->delete('verification_codes');
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