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
        foreach($oldDataArray as $oldKey => $oldVal){
            if($oldVal !== $newDataArray[$oldKey]){
                $returnArray[$oldKey] = $newDataArray[$oldKey];
            }
        }
        return $returnArray;
    }
    /**
     * compare two arrays of database rows.
     * @param newDataArray new data
     * @param oldDataArray old data
     * @param changeReceiver the array that receives changed rows, format: array of array('before'=>data, 'after'=>data)
     * @param deleteReceiver the array that receives rows that should be deleted.
     * @param addReceiver the array that receives rows that should be added.
     * @param uniqueKey which column in each row is unique? can be null
     */
    public static function compareDataRowsChange(
        array $newDataArray, 
        array $oldDataArray, 
        array &$changeReceiver,
        array &$deleteReceiver,
        array &$addReceiver,
        string $uniqueKey = NULL
    ) : void{
        //first iterate through the new Data, check if we have any added rows & modified rows.
        foreach($newDataArray as $newDataSingleRow){
            $foundThisRowInOldData = false;
            foreach($oldDataArray as $oldDataSingleRow){
                $twoRowsAreIdentical = true;
                foreach($newDataSingleRow as $newDataKey => $newDataVal){
                    if($oldDataSingleRow[$newDataKey] !== $newDataVal){
                        $twoRowsAreIdentical = false;
                        break; //from foreach loop
                    }
                }
                if(!$twoRowsAreIdentical && (!empty($uniqueKey) && $oldDataSingleRow[$uniqueKey] === $newDataSingleRow[$uniqueKey])){
                    $changeReceiver[] = array(
                        'before' => $oldDataSingleRow,
                        'after' => $newDataSingleRow
                    );
                    $foundThisRowInOldData = true;
                break;
                }
                if($twoRowsAreIdentical){
                    $foundThisRowInOldData = true;
                    break;
                }
            }
            if(!$foundThisRowInOldData){
                $addReceiver[] = $newDataSingleRow;
            }
        }

        //second iterate through the old Data, check if we have any deleted rows
        foreach($oldDataArray as $oldDataSingleRow){
            $foundThisRowInNewData = false;
            foreach($newDataArray as $newDataSingleRow){
                $twoRowsAreIdentical = true;
                foreach($oldDataSingleRow as $oldDataKey => $oldDataVal){
                    if($newDataSingleRow[$oldDataKey] !== $newDataVal){
                        $twoRowsAreIdentical = false;
                        break; //from foreach loop
                    }
                }
                if($twoRowsAreIdentical){
                    $foundThisRowInOldData = true;
                    break;
                }
            }
            if(!$foundThisRowInOldData){
                $deleteReceiver[] = $oldDataSingleRow;
            }
        }
    }
}