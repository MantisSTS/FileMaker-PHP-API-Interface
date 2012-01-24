<?php

require_once ( 'fm_api/FileMaker.php' );
require_once ( 'config/config.php' );
/**
 * Interface between the FileMaker API and PHP - Written by RichardC 
 * 
 * @author  RichardC
 * @version 1.4
 */
class FMDB {
    /**
     * Setting up the classwide variables
     */
    protected $fm;
    protected $layout = '';
    protected $debugCheck = true;
    public $lastObj = null;
	
    //Filemaker LessThan/Equal to and GreaterThan/Equal to characters - Does not work in all IDEs
    public $ltet = '≤';
    public $gtet = '≥';

    /** Constructor of the class */
    public function __construct() {
        $this->fm = new FileMaker( FMDB_NAME, FMDB_IP, FMDB_USERNAME, FMDB_PASSWORD );
    }


    /**
     * Checks whether there is an error in the resource given.
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.6
     * 
     * @param   obj     $request_object
     * 
     * @return  int
     */
    public static function isError( $request_object ) {
        if( is_array( $request_object ) && array_key_exists( 'errorCode', $request_object ) ){
            return $request_object['errorCode'];
        }
        
        return ( FileMaker::isError( $request_object ) ? $request_object->getCode() : 0 );
    }


    /** 
     * Just a quick debug function that I threw together for testing
     * 
     * @author  RichardC
     * @since   1.4
     * 
     * @version 1.4
     * 
     * @param   string  $func
     * @param   array   $arrReturn
     * @param   string  $type   'file' || 'console'
     * 
     * @return  mixed
     */
    protected function debug( $func, $arrReturn, $type='file' ){
        $debugStr = '';
        
        if( $func == '' || empty( $func ) ){
            return '';
        }
        
        switch( $type ){
            
            case 'default':
            case 'file':
            
                $fo = fopen( DEBUG_LOCATION . '/logFile.txt', 'a+'); 
    
                foreach( $arrReturn as $k => $v ){
                    
                    if( is_array( $v ) ){
                        foreach( $v as $n => $m ){
                            //$debugStr .= '<script type="text/javascript">console.log("[Debug] ' . $func . ' - ['. $n .'] -> ' . $m . ' ");</script>';
                            fwrite( $fo, '[Debug ' . date( 'd-m-Y H:i:s' ) . '] ' . $func . ' - ['. $n .'] -> ' . $m . "\n\r" );
                        }
                    }else{
                        //$debugStr .= '<script type="text/javascript">console.log("[Debug] ' . $func . ' - ['. $k .'] ' . $v . ' ");</script>';
                        fwrite( $fo,  '[Debug '. date( 'd-m-Y H:i:s' ) . '] ' . $func . ' - ['. $k .'] ' . $v . "\n\r" );
                    }
                }
                fclose( $fo );
                
                return true;
                
                break;
                
            case 'console':
                foreach( $arrReturn as $k => $v ){
                    if( is_array( $v ) ){
                        foreach( $v as $n => $m ){
                            $debugStr .= '<script type="text/javascript">console.log("[Debug] ' . $func . ' - ['. $n .'] -> ' . $m . ' ");</script>';
                        }
                    }else{
                        $debugStr .= '<script type="text/javascript">console.log("[Debug] ' . $func . ' - ['. $k .'] ' . $v . ' ");</script>';
                    }
                }
                
                return $debugStr;
                
                break;
        }
    }

    /**
     * Selects data from a FileMaker Layout from the given criteria
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.4
     * 
     * @param   string  $layout
     * @param   array   $arrSearchCriteria
     * @param   bool    $xml
     * 
     * @return  array
     */
    public function select( $layout, $arrSearchCriteria ) {
        $arrOut = array();

        if ( ( !is_array( $arrSearchCriteria ) ) ) {
            return false;
        }

        $findReq = $this->fm->newFindCommand( $layout );

        foreach ( $arrSearchCriteria as $field => $value ) {
            $findReq->addFindCriterion( $this->fm_escape_string( $field ), $this->fm_escape_string( $value ) );
        }

        $results = $findReq->execute();

        if ( $this->isError( $results ) === 0 ) {
            $fields = $results->getFields();

            $records = $results->getRecords();

            //Set the last used layout and object
            $this->layout = $layout;
            $this->lastObj = $records;

            //Loops through the records retrieved
            $i = 0;
            foreach ( $records as $record ) {
                $i++;
                foreach ( $fields as $field ) {
                    $arrOut[$i]['rec_id']   = $record->getRecordId();
                    $arrOut[$i][$field]     = $record->getField( $field );
                }
            }
        } else {
            $arrOut['errorCode'] = $this->isError( $results );
        }

        if( $this->debugCheck ){
            foreach( $arrOut as $k => $v ){
                echo $this->debug( 'SELECT', array(
                    $k  =>  $v 
                ));
            }
        }
        return $arrOut;
    }

    /**
     * Sets Fields within a given Layout with the given criteria
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.2
     * 
     * @param   array   $arrFields
     * 
     * @example $objFMDB->setFields(array('fieldName' => 'ValueToUpdate'));
     * 
     * @return  bool
     */
    public function setFields( $arrFields ) {
        $blOut = false;
        if ( ( !is_array( $arrFields ) ) ) {
            return false;
        }
        $layout = ( empty( $layout ) ? ( $this->layout ) : ( $layout ) );
        $records = $this->lastObj;

        if ( isset( $records ) && !empty( $records ) ) {
            foreach ( $records as $record ) {
                foreach ( $arrFields as $fieldName => $value ) {
                    $setFields[] = $record->setField( $this->fm_escape_string( $fieldName ), $this->fm_escape_string( $value ) );
                }
            }
            $commit = $record->commit();
            if ( $this->isError( $commit ) === 0 ) {
                $blOut = true;
            } else {
                return $this->isError( $commit );
            }
        }
        
        // Housekeeping
        unset( $record, $commit, $fieldName, $value );
        
        return $blOut;
    }
    
    
   /**
     * Updates a set of fields on a layout where the clauses match
     * 
     * @author  RichardC
     * @since   1.4
     * 
     * @version 1.0
     * 
     * @param   string  $layout
     * @param   array   $arrFields
     * @param   array   $arrSearchCriteria
     * 
     * @return  bool
     */
    public function update( $layout, $arrFields, $arrSearchCriteria ){
        
        //Loop through the parameters and check they are set and not empty
        foreach( func_get_args() as $arg ){
            if( ( $arg == '' ) || ( empty( $arg ) ) ){
                return false;
            }
        }
        
        $findReq = $this->fm->newFindCommand( $layout );

        foreach ( $arrSearchCriteria as $field => $value ) {
            $findReq->addFindCriterion( $this->fm_escape_string( $field ), $this->fm_escape_string( $value ) );
        }

        //Perform the find
        $result = $findReq->execute();
        
        if ( $this->isError( $result ) !== 0 ) {
            return $this->isError( $findReq );    
        }
        
        $records = $result->getRecords();
        
        //Loop through the found records 
        foreach ( $records as $record ) {
            
            //Loop through the fields given in the argument and set the fields with the values
            foreach ( $arrFields as $f => $v ) {
                $record->setField( $this->fm_escape_string( $f ), $this->fm_escape_string( $v ) );
            }
            
            //Commit the setFields
            $commit = $record->commit();
            
            if ( $this->isError( $commit ) !== 0 ) {
                return $this->isError( $commit );
            }
        }
        
        //Housekeeping
        unset( $result, $commit, $record, $findReq );
        
        return true;
    }



    /**
     * Updates a record by the given ID of the record on a specified layout
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.2
     * 
     * @param   string  $layout
     * @param   array   $arrFields
     * @param   int     $iRecordID
     * 
     * @return  bool
     */
    public function updateRecordByID( $layout, $arrFields, $iRecordID ) {
        $blOut = false;
        if ( ( $layout == '' ) || ( !is_array( $arrFields ) ) || ( !is_numeric( $iRecordID ) ) ) {
            return false;
        }
        $findReq = $this->fm->getRecordById( $layout, $iRecordID );

        if ( $this->isError( $findReq ) === 0 ) {

            foreach ( $findReq as $record ) {
                foreach ( $arrFields as $f => $v ) {
                    $record->setField( $this->fm_escape_string( $f ), $this->fm_escape_string( $v ) );
                }
                $commit = $record->commit();
            }

            if ( $this->isError( $commit ) === 0 ) {
                $blOut = true;
            } else {
                return $this->isError( $commit );
            }
        } else {
            return $this->isError( $findReq );
        }

        unset( $result, $commit, $record, $findReq );
        return $blOut;
    }

    /**
     * Inserts a record into the layout
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @param   string  $layout
     * @param   array   $arrFields
     * 
     * @return  bool
     */
    public function insert( $layout, $arrFields ) {
        $blOut = false;
        if ( ( $layout == '' ) || ( !is_array( $arrFields ) ) ) {
            return false;
        }

        // Auto-Sanitize the input data
        foreach ( $arrFields as $field => $value ) {
            $fields[$this->fm_escape_string( $field )] = $this->fm_escape_string( $value );
        }

        $addCmd = $this->fm->newAddCommand( $this->fm_escape_string( $layout ), $fields );
        $result = $addCmd->execute();

        if ( $this->isError( $result ) === 0 ) {
            $blOut = true;
        } else {
            return $this->isError( $result );
        }

        unset( $addCmd, $result );
        return $blOut;
    }

    /**
     * Gets the layout names within a Database
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @return  array
     */
    public function get_layout_names() {
        return $this->fm->listLayouts();
    }

    /**
     * Alias of 'select' 
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @param   string  $layout
     * @param   array   $arrSearchCriteria
     * 
     * @return  array
     */
    public function find( $layout, $arrSearchCriteria ) {
        return $this->select( $layout, $arrSearchCriteria );
    }

    /**
     * Counts the number of items in the given array
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @param   array   $arrResult
     * 
     * @return  int
     */
    public function fm_num_rows( $arrResult ) {
        $intOut = 0;
        if ( is_array( $arrResult ) ) {
            foreach ( $arrResult as $result ) {
                $intOut = count( $result );
            }
        } else {
            $intOut = count( $arrResult );
        }

        return $intOut;
    }


    /**
     * Runs a script on the layout
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @param   string  $layout
     * @param   string  $scriptName
     * @param   array   $params (optional)
     * 
     * @return  bool
     */
    public function runScript( $layout, $scriptName, $params = array() ) {
        if ( ( empty( $layout ) ) || ( empty( $scriptName ) ) ) {
            return false;
        }
        
        if ( $this->fm->newPerformScriptCommand( $layout, $scriptName, $params ) ) {
            return true;
        }
        return false;
    }

    /**
     * Get the ID of the last updated/inserted field
     * 
     * @author  RichardC
     * @since   1.2
     * 
     * @version 1.0
     * 
     * @return  int
     */
    public function getLastID() {
    }

    /**
     * Get the ID of the last updated/inserted field
     * 
     * @author  RichardC
     * @since   1.2
     * 
     * @version 1.0
     * 
     * @return  int
     */
    public function getLastID() {
    }

    /**
     * Deletes a record from the table/layout with the given record ID
     * 
     * @author  RichardC
     * @since   1.2
     * 
     * @version 1.0
     * 
     * @return  bool
     */
    public function deleteRecordByID( $layout, $iRecordID ) {
        
        foreach( func_get_args() as $arg ){
            if( empty( $arg ) || $arg == '' ){
                return false;
            }
        }
        
        $delete = $this->fm->newDeleteCommand( $layout, $iRecordID ); 
        $delResult = $delete->execute();
        
        if( $this->isError( $delResult ) ){
            return $this->isError( $delResult );
        }

        unset( $delete, $delResult, $layout, $iRecordID );
        return true;
    }
    
    
    /**
     * Deletes a record where the search criteria matches
     * 
     * @author  RichardC
     * @since   1.4
     * 
     * @version 1.0
     * 
     * @param   string  $layout
     * @param   array   $arrSearchCriteria
     * 
     * @return int      The amount of records deleted || errorCode
     */
    public function delete( $layout, $arrSearchCriteria ){
        if( empty( $layout ) || empty( $arrSearchCriteria ) ){
            return 0;
        }
        
        //Performs the search
        $search = $this->select( $layout, $arrSearchCriteria );
        
        if( empty( $search ) ){
            return 0;
        }
        
        //Checks for an error
        if( array_key_exists( 'errorCode', $search ) ){
            return $search['errorCode'];
        }
        
        $i = 0;
        foreach( $search as $records ){
            
            $delete = $this->deleteRecordByID( $layout, $records['rec_id'] );
            
            // Errors return as strings so thats why the check is to make sure its an integer
            if( !is_int( $delete ) ){
                return $delete; //replace $delete with 0; after testing
            }
            $i++;
        }
        
        return $i;
    }
    
    /*
     * Gets the ID of the record in the last Select
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @return  int
     */
    public function getRecordId() {
        return $this->lastObj->getRecordId();
    }

    /**
     * Escapes a string manually
     * 
     * @author  RichardC
     * @since   1.0
     * 
     * @version 1.0
     * 
     * @param   string  $input
     * 
     * @return  string
     */
    public function fm_escape_string( $input ) {
        if ( is_array( $input ) ) {
            return array_map( __method__, $input );
        }

        if ( !empty( $input ) && is_string( $input ) ) {
            return str_replace( array( '\\', '/', "\0", "\n", "\r", "'", '"', "\x1a", '<', '>' ), array( '\\\\', '\/', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z', '\<\\/', '\\/>' ), $input );
        }
        return $input;
    }
}

?>