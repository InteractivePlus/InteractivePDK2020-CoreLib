<?php
namespace InteractivePlus\PDK2020Core\Implementions\Storages;

use InteractivePlus\PDK2020Core\Exceptions\PDKException;
use InteractivePlus\PDK2020Core\Interfaces\Storages\ProfileImageStorage;
use MysqliDb;

class ProfileImageMySQLImpl implements ProfileImageStorage{
    private $_Database;

    public function __construct(MysqliDb $database){
        $this->_Database = $database;
    }

    public function getDatabase() : MysqliDb{
        return $this->_Database;
    }
    /**
     * @inheritdoc
     */
    public function uploadProfileImage(string $imageData) : string{
        $imageHash = strtolower(md5($imageData));
        if($this->profileImageExists($imageHash)){
            return $imageHash;
        }
        $dataArray = array(
            'hash' => $imageHash,
            'data' => gzcompress($imageData)
        );
        $insertStatus = $this->_Database->insert('avatars',$dataArray);
        if(!$insertStatus){
            throw new PDKException(
                50007,
                __CLASS__ . ' insert error',
                array(
                    'errNo'=>$this->_Database->getLastErrno(),
                    'errMsg'=>$this->_Database->getLastError()
                )
            );
        }
        return $imageHash;
    }

    /**
     * @inheritdoc
     */
    public function getProfileImageData(string $hash) : ?string{
        $this->_Database->where('hash',strtolower($hash));
        $data = $this->_Database->getOne('avatars');
        if(!$data || empty($data)){
            return NULL;
        }
        return $data['data'];
    }

    /**
     * @inheritdoc
     */
    public function profileImageExists(string $hash) : bool{
        $this->_Database->where('hash',strtolower($hash));
        $count = $this->_Database->getValue('avatars','count(*)');
        if($count >= 1){
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function deleteProfileImage(string $hash) : void{
        $this->_Database->where('hash',strtolower($hash));
        $deleteStatus = $this->_Database->delete('avatars');
        if(!$deleteStatus){
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
}