<?php
namespace InteractivePlus\PDK2020Core\Utils;

use InteractivePlus\PDK2020Core\Exceptions\TemplateException;

class TemplateEngine{
    private $templateString;
    public $displayNullValue = false;
    public $abortError = false;
    public function __construct(string $templateContent)
    {
        $this->templateString = $templateContent;
    }
    public function render(array $variables) : string{
        //首先我们要处理variable list
        $newVariables = self::fixVariableList($variables);
        //查找, 定位 {{ 以确定当前Variable位置
        $currentOffset = 0;
        $totalCharNum = strlen($this->templateString);
        $currentLevel = 0;
        $processedTemplate = $this->templateString;
        while($currentOffset < strlen($processedTemplate)){
            $nextVariableEnterZone = strpos($processedTemplate,'{{',$currentOffset);
            $nextVariableExitZone = strpos($processedTemplate,'}}',$currentOffset);
            if($nextVariableEnterZone < 0 && $nextVariableExitZone < 0){
                break;
            }
            if($nextVariableExitZone < $nextVariableEnterZone){
                if(!$this->abortError){
                    throw new TemplateException(0,'End variable indicator cannot appear before start variable indicator');
                }else{
                    //Abort this loop, let's go on to the next loop.
                    $currentOffset = $nextVariableEnterZone;
                    continue;
                }
            }
            $strippedVariableZone = substr(
                $processedTemplate,
                $nextVariableEnterZone + strlen('{{'),
                $nextVariableExitZone - $nextVariableEnterZone - strlen('{{')
            );
            $expressionVal = $this->getExpressionValue($strippedVariableZone,$newVariables);
            $processedTemplate = substr_replace(
                $processedTemplate,
                $expressionVal,
                $nextVariableEnterZone,
                $nextVariableExitZone - $nextVariableEnterZone + strlen('}}')
            );
            $nextVariableExitZoneNewPtr = $nextVariableEnterZone + strlen($expressionVal);
            $currentOffset = $nextVariableExitZoneNewPtr;
        }
        return $processedTemplate;
    }
    public function getExpressionValue(string $expression, array $fixedVariableList){
        $fixedExpression = self::fixVariableName($expression);
        $variableValue = $fixedVariableList[$fixedExpression];
        if(!isset($fixedVariableList[$fixedExpression])){
            if(!$this->abortError){
                throw new TemplateException(0,'Variable ' . $fixedExpression . ' not passed to template engine');
            }
        }
        if($variableValue === NULL){
            if($this->displayNullValue){
                return 'NULL';
            }else{
                return '';
            }
        }else{
            return $variableValue;
        }
    }
    public function quickRender(array $variables) : string{
        $processedTemplate = $this->templateString;
        foreach($variables as $var => $val){

        }
    }
    public static function fixVariableList(array $variableList) : array{
        $newVariableList = array();
        foreach($variableList as $var => $val){
            $newVariableList[self::fixVariableName($var)] = $val;
        }
        return $newVariableList;
    }
    public static function fixVariableName(string $variableName) : string{
        return strtolower(trim($variableName));
    }
    public static function renderPage(string $template, array $variableList) : string{
        TemplateEngine mEngine = new TemplateEngine($template)
    }
}
?>