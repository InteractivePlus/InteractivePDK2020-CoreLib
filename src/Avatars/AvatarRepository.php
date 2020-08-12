<?php
namespace InteractivePlus\PDK2020Core\Avatars;

use InteractivePlus\PDK2020Core\Interfaces\Storages\ProfileImageStorage;
use InteractivePlus\PDK2020Core\Settings\Setting;

class AvatarRepository implements ProfileImageStorage{
    private $_storageObj = NULL;

    public function __construct(ProfileImageStorage $customStorage){
        $this->_storageObj = $customStorage;
    }

    public function getStorage() : ProfileImageStorage{
        return $this->_storageObj;
    }

    public function setStorage(ProfileImageStorage $storage){
        $this->_storageObj = $storage;
    }

    /**
     * @inheritdoc
     */
    public function uploadProfileImage(string $imageData) : string{
        $image = imagecreatefromstring($imageData);
        $imageSize = getimagesizefromstring($imageData); //index 0 and 1 returns width and height respectively
        $imageWidth = $imageSize[0];
        $imageHeight = $imageSize[1];

        $actualImageToSave = $image;

        if($imageWidth > Setting::AVATAR_SIZE_PIXELS || $imageHeight > Setting::AVATAR_SIZE_PIXELS){
            $newWidth = Setting::AVATAR_SIZE_PIXELS;
            $newHeight = Setting::AVATAR_SIZE_PIXELS;
            $newImage = imagecreatetruecolor($newWidth,$newHeight);
            imagecopyresampled($newImage,$image,0,0,0,0,$newWidth,$newHeight,$imageWidth,$imageHeight);
            $actualImageToSave = $newImage;
        }
        
        $imageReceiverStream = fopen('php://memory','r+');
        imagewebp($actualImageToSave,$imageReceiverStream);
        rewind($imageReceiverStream);
        $actualData = stream_get_contents($imageReceiverStream);
        fclose($imageReceiverStream);
        
        if($actualImageToSave !== $image){
            imagedestroy($actualImageToSave);
        }
        imagedestroy($image);

        return $this->_storageObj->uploadProfileImage($actualData);
    }



    /**
     * @inheritdoc
     */
    public function getProfileImageData(string $hash) : ?string{
        return $this->_storageObj->getProfileImageData($hash);
    }

    /**
     * @inheritdoc
     */
    public function profileImageExists(string $hash) : bool{
        return $this->_storageObj->profileImageExists($hash);
    }

    /**
     * @inheritdoc
     */
    public function deleteProfileImage(string $hash) : void{
        return $this->_storageObj->deleteProfileImage($hash);
    }

}