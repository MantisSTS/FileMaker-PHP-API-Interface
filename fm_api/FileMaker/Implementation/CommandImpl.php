<?php
require_once dirname(__FILE__) . '/../Error/Validation.php';
require_once dirname(__FILE__) . '/../Result.php';
class FileMaker_Command_Implementation {
    var $_fm;
    var $_layout;
    var $_result_layout;
    var $_script;
    var $_scriptParams;
    var $_preReqScript;
    var $_preReqScriptParams;
    var $_preSortScript;
    var $_preSortScriptParams;
    var $V0b9a204c;
    var $_recordId;
    function FileMaker_Command_Implementation($fm, $layout) {
        $this->_fm =& $fm;
        $this->_layout = $layout; 
        $this->V0b9a204c= $fm->getProperty('recordClass');
    }
    function setResultLayout($layout) {
        $this->_result_layout= $layout;
    }
    function setScript($script, $scriptParams = null) {
        $this->_script = $script;
        $this->_scriptParams = $scriptParams;
    }
    function setPreCommandScript($script, $scriptParams = null) {
        $this->_preReqScript = $script;
        $this->_preReqScriptParams = $scriptParams;
    }
    function setPreSortScript($script, $scriptParams = null) {
        $this->_preSortScript = $script;
        $this->_preSortScriptParams = $scriptParams;
    }
    function setRecordClass($V6f66e878) {
        $this->V0b9a204c= $V6f66e878;
    }
    function setRecordId($recordId) {
        $this->_recordId = $recordId;
    }
    function validate($V972bf3f0 = null) {  
        if (!is_a($this, 'FileMaker_Command_Add_Implementation') && !is_a($this, 'FileMaker_Command_Edit_Implementation')) {
            return true;
        }
        $layout = & $this->_fm->getLayout($this->_layout);
        if (FileMaker :: isError($layout)) {
            return $layout;
        } 
        $Vcb5e100e = & new FileMaker_Error_Validation($this->_fm);
        if ($V972bf3f0 === null) {   
            foreach ($layout->getFields() as $V972bf3f0 => $V06e3d36f) {  
                if (!isset ($this->_fields[$V972bf3f0]) || !count($this->_fields[$V972bf3f0])) {
                    $Vf09cc7ee = array (0 => null);
                } else {
                    $Vf09cc7ee = $this->_fields[$V972bf3f0];
                }
                foreach ($Vf09cc7ee as $V2063c160) {
                    $Vcb5e100e = $V06e3d36f->validate($V2063c160, $Vcb5e100e);
                }
            }
        } else { 
            $V06e3d36f = & $layout->getField($V972bf3f0);
            if (FileMaker :: isError($V06e3d36f)) {
                return $V06e3d36f;
            }  
            if (!isset ($this->_fields[$V972bf3f0]) || !count($this->_fields[$V972bf3f0])) {
                $Vf09cc7ee = array (0 => null);
            } else {
                $Vf09cc7ee = $this->_fields[$V972bf3f0];
            }
            foreach ($Vf09cc7ee as $V2063c160) {
                $Vcb5e100e = $V06e3d36f->validate($V2063c160, $Vcb5e100e);
            }
        }  
        return $Vcb5e100e->numErrors() ? $Vcb5e100e : true;
    }
    function & _getResult($V0f635d0e) {
        $V3643b863 = & new FileMaker_Parser_FMResultSet($this->_fm);
        $Vb4a88417 = $V3643b863->parse($V0f635d0e);
        if (FileMaker :: isError($Vb4a88417)) {
            return $Vb4a88417;
        }
        $Vd1fc8eaf = & new FileMaker_Result($this->_fm);
        $Vb4a88417 = $V3643b863->setResult($Vd1fc8eaf, $this->V0b9a204c);
        if (FileMaker :: isError($Vb4a88417)) {
           return $Vb4a88417;
        }
        return $Vd1fc8eaf;
    }
    function _getCommandParams() { 
        $V21ffce5b = array ('-db' => $this->_fm->getProperty('database'), '-lay' => $this->_layout); 
        foreach (array ('_script' => '-script','_preReqScript' => '-script.prefind','_preSortScript' => '-script.presort') as $Vb2145aac => $Veca07335) {
            if ($this-> $Vb2145aac) {
                $V21ffce5b[$Veca07335] = $this-> $Vb2145aac;
                $Vb2145aac .= 'Params';
                if ($this-> $Vb2145aac) {
                    $V21ffce5b[$Veca07335 . '.param'] = $this-> $Vb2145aac;
                }
            }
        } 
        if ($this->_result_layout) {
            $V21ffce5b['-lay.response'] = $this->_result_layout;
        }
        return $V21ffce5b;
    }
}
?>