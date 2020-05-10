<?php
namespace InteractivePlus\PDK2020Core\Utils;
class DataUtil{
    public static function compareDataArrayDifference(array $newDataArray, array $oldDataArray) : array{
        $returnArray = array();
        foreach($newDataArray as $newKey => $newVal){
            if($newVal !== $oldDataArray[$newKey]){
                $returnArray[$newKey] = $newVal;
            }
        }
        return $returnArray;
    }
}