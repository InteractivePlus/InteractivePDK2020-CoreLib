<?php
namespace InteractivePlus\PDK2020Core\Interfaces\Storages;
interface ProfileImageStorage{
    
    /**
     * Save the profile image to the storage
     * @param imageInPNGFormat a string that contains byte data of the profile image in any supported format
     * @return imageMD5 the MD5 hash of the image file
     */
    public function uploadProfileImage(string $imageData) : string;

    /**
     * Retrieve image file from storage
     * @param hash the MD5 hash of the image file
     * @return imageData NULLABLE, image data in any supported format.
     */
    public function getProfileImageData(string $hash) : ?string;

    /**
     * Check if image file exists in storage
     * @param hash the MD5 hash of the image file
     * @return existState if the image with given hash exists, return true
     */
    public function profileImageExists(string $hash) : bool;

    /**
     * Delete an image file from the storage
     * @param hash the MD5 hash of the image file
     */
    public function deleteProfileImage(string $hash) : void;

}