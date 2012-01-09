<?php
require_once dirname(__FILE__) . '/../CommandImpl.php';
class FileMaker_Command_PerformScript_Implementation extends FileMaker_Command_Implementation {
    function FileMaker_Command_PerformScript_Implementation($fm, $layout, $scriptName, $scriptParams = null){
        FileMaker_Command_Implementation::FileMaker_Command_Implementation($fm, $layout);
        $this->_script = $scriptName;
        $this->_scriptParams = $scriptParams;
    }
    function execute() { 
        $commandParams = $this->_getCommandParams(); 
        $commandParams['-findany'] = true; 
        $result = $this->_fm->_execute($commandParams);
        if (FileMaker::isError($result)) {
            return $result;
        } 
        return $this->_getResult($result);
    }
}
?>