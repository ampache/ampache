<?php
/**
 * Load the Getid3 Library
 */
require_once(conf('prefix') . '/modules/id3/getid3/getid3.php');


/**
 * vainfo
 * This class takes the information pulled from getID3 and returns it in a
 * Ampache friendly way. 
 */
class vainfo { 

	/* Default Encoding */
	var $encoding = 'UTF-8';
	

	/* Loaded Variables */
	var $filename = '';
	var $_getID3 = '';
	

	/* Returned Variables */
	var $_info = array();

	/**
	 * Constructor
	 * This function just sets up the class, it doesn't
	 * actually pull the information
	 */
	function vainfo($file,$encoding='') { 

		$this->filename = stripslashes($file);
		if ($encoding) { 
			$this->encoding = $encoding;
		}

                // Initialize getID3 engine
                $this->_getID3 = new getID3();
                $this->_getID3->option_md5_data          = false;
                $this->_getID3->option_md5_data_source   = false;
                $this->_getID3->encoding                 = $this->encoding;

	} // vainfo


	/**
	 * get_info
	 * This function takes a filename and returns the $_info array
	 * all filled up with tagie goodness or if specified filename
	 * pattern goodness
	 */
	function get_info() {

                $raw_array = $this->_getID3->analyze($this->filename);

		print_r($raw_array);

	} // get_info

} // end class vainfo
?>
