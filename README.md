# [FileMaker PHP API Interface by RichardC]

The FileMaker PHP API Interface written by RichardC is an easier way for you to write applications from the PHP API from FileMaker.
All you will have to do is download the files, configure the config file and require the class in where ever you want to query the FileMaker Database.

## Dependencies
- FileMaker PHP API
- FileMaker Server (To get the FileMaker PHP API)
- PHP 5.3+ (Recommended)
- cURL enabled


## Versioning
Releases will be numbered with the following format:
`<major>.<minor>.<patch>` 
For example: `1.4.6`


## Documentation
Documentation is currently being worked on and will be released by April 14th 2012.

##[Class] FMDB

###[Functions]
Please note that all functions get automatically sanitized using my fm_escape_string() function.

1. **[Public Static] isError( $request_object )** - Checks whether a given object returns an error, if it does it will tell you which error otherwise it will return 0
    Example: 
    
        if( FMDB::isError( $request_object ) != 401 ){
            echo 'Records were found!';
        }
        
2. **[Public] select( $layout, $arrFields )** - Gets a list of fields and values from a given layout. It will return an array.
    Example:
    
        $select = $fmdb->select( 'Layout', array(
            'fieldName'     =>  'valueToSearch',
            'fieldName2'    =>  'valueToSearch'
        )); 
        
        if( is_array( $select ) ){
            foreach( $select as $result ){
                echo $result['fieldName'];
            }
        }
        
3. **[Public] setFields( $arrFields )** - This function will set fields from the previously used object (normally from the last $fmdb->select() function). Returns boolean
    Example:
    
        $select = $fmdb->select( 'Layout', array(
            'fieldName'     =>  'valueToSearch',
            'fieldName2'    =>  'valueToSearch'
        )); 
        
        if( $select ){  
            $set = $fmdb->setFields( array(
                'FieldName'  => 'ValueOne'
            ));
            
            if( $set == true ){
                echo 'Updated!';
            }
        }
    
4. **[Public] updateRecordByID( $layout, $arrFields, $iRecordID )** - Updates a record by using it's ID which you can get by using the select function and then using the 'rec_id' field. Returns Boolean
    Example:
    
        $updateRec = $fmdb->updateRecordByID( 'Layout', array(
            'Field' =>  'Value'
        ), 123);
        
5. **[Public] insert( $layout, $arrFields )**

6. **[Public] get_layout_names()**

7. **[Public] find( $layout, $arrSearchCriteria )** - Alias of Select

8. **[Public] fm_num_rows( $arrResult )** - Returns the number of rows in a result array.

9. **[Public] runScript( $layout, $scriptName, $params = array() )** - Runs a script on the FileMaker server

10. **[Public] getRecordId()** - Returns the record ID of the last object

11. **[Public] fm_escape_string( $input )** - Escapes any unwanted characters in an input string



## License

Copyright (c) 2011 Cysha Ltd

The FileMaker PHP API interface written by RichardC is licensed under the [GPLv3 license](http://www.gnu.org/licenses/gpl-3.0.html).