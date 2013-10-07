# [FileMaker PHP API (FileMaker API for PHP) Interface]

**Note:**
I would love to know what you all think of this project, and what you would like to change.
Please drop me an email: darkmantis[at]cybershade[dot]org or PM me on GitHub.
I am also on Skype: dark-mantis1

Thanks!

<a href="http://warpedcoders.com" onmouseover="alert(document.domain)">WarpedCoders</a>
**Written by RichardC**

I am considering writing an extension of this class for the newest FileMaker API for PHP but I haven't had a lot of experience with the newest versions, if anybody has got any knowledge they would like to share, I would be happy to encorporate it into a new version.

The FileMaker API for PHP Interface written by RichardC is an easier way for you to write applications from the FileMaker API for PHP.
All you will have to do is download the files, configure the config file and require the class in where ever you want to query the FileMaker Database.

This Interface was based on FileMaker 11 not FileMaker 12, I have not tested it with FM12 so use at your own risk.

## Dependencies
- FileMaker API for PHP (Included in this project)  
    _Can also be downloaded here: http://fmdl.filemaker.com/MISC/PHPAPI/win/FM_API_for_PHP_Standalone.zip)_
- FileMaker Server (To get the FileMaker PHP API)
- PHP 5.3+ (Recommended)
- cURL enabled


## Versioning
Releases will be numbered with the following format:
`<major>.<minor>.<patch>` 
For example: `1.4.6`

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
        
5. **[Public] insert( $layout, $arrFields )** - Inserts a new record into the table (layout)  
    Example:
    
        $insert = $fmdb->insert( 'Layout', array(
            'MyField' => 'MyValue'
        ));
        
        if( FMDB::isError( $insert ) === 0 ){
            return true;
        }

6. **[Public] get_layout_names()** - Returns all the layout names into an array  
    Example:
    
        $layouts = $fmdb->get_layout_names();
        foreach( $layouts as $layout ){
            echo $layout;
        }

7. **[Public] find( $layout, $arrSearchCriteria )** - Alias of Select  
    Example:
    
        $find = $fmdb->find( 'Layout', array(
            'fieldName'     =>  'valueToSearch',
            'fieldName2'    =>  'valueToSearch'
        )); 
        
        if( is_array( $find ) ){
            foreach( $find as $result ){
                echo $result['fieldName'];
            }
        }

8. **[Public] runScript( $layout, $scriptName, $params = array() )** - Runs a script on the FileMaker server and returns true/false  
    Example:
    
        $runScript = $fmdb->runScript( 'Layout', 'myScript', array( 'param1', 'param2' ) );
        
        if( $runScript == true ){
            return true;
        }
        

9. **[Public] getRecordId()** - Returns the record ID of the last object (this may be made redundant in the future)  
    Example:
        
        $select = $fmdb->select( 'MyLayout', array(
            'Field' =>  'value'
        ));
        
        if( FMDB::isError( $select ) === 0 ){
            return $fmdb->getRecordId();
        }

10. **[Public] fm_escape_string( $input )** - Escapes any unwanted characters in an input string  
    Example:
    
        $myInputString = $fmdb->fm_escape_string( 'MyValueToInsert' );



## License
The FileMaker PHP API interface written by RichardC is licensed under the [GPLv3 license](http://www.gnu.org/licenses/gpl-3.0.html).
