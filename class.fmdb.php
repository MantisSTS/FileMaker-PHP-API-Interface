<?php

require_once ( 'fm_api/FileMaker.php' );
require_once ( 'config/config.php' );

/**
 * Interface between the FileMaker API and PHP - Written By RichardC
 *
 * @author  RichardC
 * @version 1.8.0
 *
 * @license GPLv3
 */
class FMDB {
    /*
    * Filemaker LessThan/Equal to and GreaterThan/Equal to characters
    * Does not work in all IDE's
    *
    * Update:
    *   Reason why I have defined these as a constant is because they will
    *   never change. I have left them as a variable for those whom have already
    *   started using it as a variable
    */
    const LTET = '≤';
    const GTET = '≥';

    /**
     * Setting up the classwide variables
     */
    protected $fm,
              $layout = '',
              $debugCheck = true,
              $fieldList = array();

    public $revertedData = array(),
           $lastObj = null,
           $ltet = self::LTET,
           $gtet = self::GTET;


    /** Constructor of the class */
    public function __construct() {
        //Performs all the relative checks that are required by the FM PHP API
        $this->doChecks();
        $this->fm = new FileMaker( FMDB_NAME, FMDB_IP, FMDB_USERNAME, FMDB_PASSWORD );
    }


    /**
     * Perform all checks before doing any thing
     *
     * @todo    Will extend this function to perfrom more extensive tests
     *
     * @author  RichardC
     * @version 1.0
     *
     * @since   1.6
     *
     * @return true
     */
    protected function doChecks(){
        if( !function_exists( 'curl_init' ) ){
            die( 'Please enable cURL to use the FileMaker PHP API' );
        }

        return true;
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

        if( is_array( $request_object ) && preg_grep( '/^([^*)]*)error([^*)]*)$/', array_keys( $request_object ) ) ){
            foreach( $request_object as $key => $val ){
                return (int)$val;
            }
        }

        return ( FileMaker::isError( $request_object ) ? (int)$request_object->getCode() : 0 );
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
            return null;
        }

        $debugStr = '';

        switch( $type ){
            default:
            case 'file':

                $fo = fopen( DEBUG_LOCATION, 'a+' );

                foreach( $arrReturn as $k => $v ){

                    $v = ( is_array( $v ) ? $v : array( $k => $v ) );

                    foreach( $v as $n => $m ){
                        $debugStr .= sprintf(
                            '[Debug %s] %s - [ %s ] -> %s %s',
                            date( 'd-m-Y H:i:s' ),
                            $func,
                            $n,
                            $m,
                            "\n"
                        );
                    }
                }
                fwrite( $fo, $debugStr );
                fclose( $fo );

                return true;

                break;

            case 'console':
                $debugStr = '<script type="text/javascript">';

                foreach( $arrReturn as $k => $v ){
                    $v = ( is_array( $v ) ? $v : array( $k => $v ) );

                    foreach( $v as $n => $m ){
                        $debugStr .= sprintf(
                            'console.log("[Debug] %s - %s -> %s ");'
                            $func,
                            $n,
                            $m
                        );
                    }
                }

                $debugStr .= '</script>';
                
                return $debugStr;
                break;
        }
    }

    /**
     * Simular to select but just returns the fields which you wanted
     *
     * @todo    Figure out a way to reduce the amount of loops or make the loops faster
     *
     * @warning This function is discouraged for a large amount of data due to the amount of loops
     *
     * @author  RichardC
     * @since   1.6
     *
     * @version 1.0
     *
     * @param   string  $layout
     * @param   array   $arrSearchCriteria
     * @param   array   $arrFields
     *
     * @return array
     */
    public function getFields( $layout, $arrSearchCriteria, $arrFields ){
        $arrOut = array();

        if( !ctype_alnum( (string)$layout ) || !is_array( $arrSearchCriteria ) ){
            return $arrOut;
        }

        // If no fields are specified then perform a normal select
        if( empty( $arrFields ) || !is_array( $arrFields ) ){
            return $this->select( $layout, $arrSearchCriteria );
        }

        // Perform the select
        $select = $this->select( $layout, $arrSearchCriteria );

        if( !$this->isError( $select ) ){

            // Loop through the returned fields
            foreach( $select as $field => $contents ){

                // Loop through the desired fields
                foreach( $arrFields as $f ){
                    if( $field == $f ){
                        $arrOut[$f] = $contents;
                    }
                }
            }
        }

        return $arrOut;
    }


    /**
     * Selects data from a FileMaker Layout from the given criteria
     *
     * @author  RichardC
     * @since   1.0
     *
     * @version 1.6
     *
     * @param   string  $layout
     * @param   array   $arrSearchCriteria
     * @param   bool    $sanitize
     *
     * @return  array
     */
    public function select( $layout, $arrSearchCriteria, $sanitize = true ) {
        $arrOut = array();

        if ( ( !is_array( $arrSearchCriteria ) ) ) {
            return false;
        }

        $findReq = $this->fm->newFindCommand( $layout );

        foreach ( $arrSearchCriteria as $field => $value ) {

            $field = ( $sanitize ? $this->fm_escape_string( $field ) : $field );
            $value = ( $sanitize ? $this->fm_escape_string( $value ) : $value );

            $findReq->addFindCriterion( $field, $value );
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
                $this->debug( 'SELECT', array(
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
     * @version 1.3
     *
     * @param   array   $arrFields
     * @param   bool    $sanitize
     *
     * @example $objFMDB->setFields( array( 'fieldName' => 'ValueToUpdate' ) );
     *
     * @return  bool
     */
    public function setFields( $arrFields, $sanitize = true ) {
        $blOut = false;
        if ( ( !is_array( $arrFields ) ) ) {
            return false;
        }
        $layout = ( empty( $layout ) ? ( $this->layout ) : ( $layout ) );
        $records = $this->lastObj;

        if ( isset( $records ) && !empty( $records ) ) {
            foreach ( $records as $record ) {
                foreach ( $arrFields as $fieldName => $value ) {

                    $fieldName = ( $sanitize ? $this->fm_escape_string( $fieldName ) : $fieldName );
                    $value = ( $sanitize ? $this->fm_escape_string( $value ) : $value );

                    $recrd->setField( $fieldName, $value );

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
     * Updates a record by the given ID of the record on a specified layout
     *
     * @author  RichardC
     * @since   1.0
     *
     * @version 1.3
     *
     * @param   string  $layout
     * @param   array   $arrFields
     * @param   int     $iRecordID
     * @param   bool    $sanitize
     *
     * @return  bool
     */
    public function updateRecordByID( $layout, $arrFields, $iRecordID, $sanitize = true ) {
        if ( ( $layout == '' ) || ( !is_array( $arrFields ) ) || ( !is_numeric( $iRecordID ) ) ) {
            return false;
        }
        $findReq = $this->fm->getRecordById( $layout, $iRecordID );

        if ( $this->isError( $findReq ) === 0 ) {

            foreach ( $findReq as $record ) {
                foreach ( $arrFields as $f => $v ) {

                    $f = ( $sanitize ? $this->fm_escape_string( $f ) : $f );
                    $v = ( $sanitize ? $this->fm_escape_string( $v ) : $v );

                    $record->setField( $f, $v );


                }
                $commit = $record->commit();
            }

            if ( $this->isError( $commit ) === 0 ) {
                return true;
            } else {
                return $this->isError( $commit );
            }
        } else {
            return $this->isError( $findReq );
        }

        unset( $result, $commit, $record, $findReq );
        return false;
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
     * @param   bool    $sanitize
     *
     * @return  bool
     */
    public function insert( $layout, $arrFields, $sanitize = true ) {
        $blOut = false;
        if ( ( $layout == '' ) || ( !is_array( $arrFields ) ) ) {
            return false;
        }

        // Auto-Sanitize the input data
        foreach ( $arrFields as $field => $value ) {

            $field = ( $sanitize ? $this->fm_escape_string( $field ) : $field );
            $value = ( $sanitize ? $this->fm_escape_string( $value ) : $value );

            $fields[$field] = $value;
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
     * Gets the layout names within a Database
     *
     * Alias of get_layout_names()
     *
     * @author  RichardC
     * @since   1.0
     *
     * @version 1.0
     *
     * @return  array
     */
    public function getLayouts(){
        return $this->get_layout_names();
    }


    /**
     * Updates a set of fields on a layout where the clauses match
     *
     * @author  RichardC
     * @since   1.4
     *
     * @version 1.1
     *
     * @param   string  $layout
     * @param   array   $arrFields
     * @param   array   $arrSearchCriteria
     * @param   bool    $sanitize
     *
     * @return  bool
     */
    public function update( $layout, $arrFields, $arrSearchCriteria, $sanitize = true ){

        //Loop through the parameters and check they are set and not empty
        foreach( func_get_args() as $arg ){
            if( ( $arg == '' ) || ( empty( $arg ) ) ){
                return false;
            }
        }

        $findReq = $this->fm->newFindCommand( $layout );

        foreach ( $arrSearchCriteria as $field => $value ) {
            $field = ( $sanitize ? $this->fm_escape_string( $field ) : $field );
            $value = ( $sanitize ? $this->fm_escape_string( $value ) : $value );

            $findReq->addFindCriterion( $field, $value );

        }

        //Perform the find
        $result = $findReq->execute();

        if ( $this->isError( $result ) !== 0 ) {
            return $this->isError( $result );
        }

        $records = $result->getRecords();

        //Loop through the found records
        foreach ( $records as $record ) {

            //Loop through the fields given in the argument and set the fields with the values
            foreach ( $arrFields as $f => $v ) {

                $f = ( $sanitize ? $this->fm_escape_string( $f ) : $f );
                $v = ( $sanitize ? $this->fm_escape_string( $v ) : $v );

                $record->setField( $f, $v );

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
     * Runs a script on the layout
     *
     * @author  RichardC
     * @since   1.0
     *
     * @version 1.0.2
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

        return ( $this->fm->newPerformScriptCommand( $layout, $scriptName, $params ) ? true : false );
    }

    /**
     * Deletes a record from the table/layout with the given record ID
     *
     * @author  RichardC
     * @since   1.2.0
     *
     * @version 1.0.2
     *
     * @return  bool
     */
    public function deleteRecordByID( $layout, $iRecordID ) {
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
     * @since   1.4.0
     *
     * @version 1.0.0
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
        if( $this->isError( $search ) !== 0 ){
            return $this->isError( $search );
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

    /**
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
     * @version 1.1
     *
     * @param   string  $input
     * @param   bool    $editCmd
     *
     * @return  string
     */
    public function fm_escape_string( $input, $editCmd = false ) {
        if ( is_array( $input ) ) {
            return array_map( __method__, $input );
        }

        if ( !empty( $input ) && is_string( $input ) ) {

            $searchCriteria = array(
                '\\',
                '/',
                "\0",
                "\n",
                "\r",
                "'",
                '"',
                "\x1a",
                '<',
                '>',
                '%00'
            );

            $replacements = array(
                '\\\\',
                '\/',
                '\\0',
                '\\n',
                '\\r',
                "\\'",
                '\\"',
                '\\Z',
                '\<\\/',
                '\\/>',
                ''
            );

            // Really not the most elegant solution but it _should_ work
            // Fixes bug #1 - Thanks to interpreet99 on GitHub
            if( $editCmd === true ){
                $searchCriteria[] = '*';
                $searchCriteria[] = '@';

                $replacements[] = '\*';
                $replacements[] = '\@';
            }

            $input = str_replace( $searchCriteria, $replacements, $input );

            return $input;
        }
    }
}

?>