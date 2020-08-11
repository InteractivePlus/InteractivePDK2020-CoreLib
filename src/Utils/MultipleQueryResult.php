<?php
namespace InteractivePlus\PDK2020Core\Utils;
class MultipleQueryResult{
    public $resultOffset;
    public $resultCount;
    public $overallCount;
    public $resultDatas;
    public function __construct(int $offset, int $count, int $overallCount, ?array $dataRows){
        $this->resultOffset = $offset;
        $this->resultCount = $count;
        $this->overallCount = $overallCount;
        $this->resultDatas = $dataRows;
    }
}