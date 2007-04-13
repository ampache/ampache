<?php
/**
 * xajaxResponse.inc.php :: xajax XML response class
 *
 * xajax version 0.2.4
 * copyright (c) 2005 by Jared White & J. Max Wilson
 * http://www.xajaxproject.org
 *
 * xajax is an open source PHP class library for easily creating powerful
 * PHP-driven, web-based Ajax Applications. Using xajax, you can asynchronously
 * call PHP functions and update the content of your your webpage without
 * reloading the page.
 *
 * xajax is released under the terms of the LGPL license
 * http://www.gnu.org/copyleft/lesser.html#SEC3
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * @package xajax
 * @version $Id$
 * @copyright Copyright (c) 2005-2006  by Jared White & J. Max Wilson
 * @license http://www.gnu.org/copyleft/lesser.html#SEC3 LGPL License
 */

/*
   ----------------------------------------------------------------------------
   | Online documentation for this class is available on the xajax wiki at:   |
   | http://wiki.xajaxproject.org/Documentation:xajaxResponse.inc.php         |
   ----------------------------------------------------------------------------
*/

/**
 * The xajaxResponse class is used to create responses to be sent back to your
 * Web page.  A response contains one or more command messages for updating
 * your page.
 * Currently xajax supports 21 kinds of command messages, including some common
 * ones such as:
 * <ul>
 * <li>Assign - sets the specified attribute of an element in your page</li>
 * <li>Append - appends data to the end of the specified attribute of an
 * element in your page</li>
 * <li>Prepend - prepends data to the beginning of the specified attribute of
 * an element in your page</li>
 * <li>Replace - searches for and replaces data in the specified attribute of
 * an element in your page</li>
 * <li>Script - runs the supplied JavaScript code</li>
 * <li>Alert - shows an alert box with the supplied message text</li>
 * </ul>
 *
 * <i>Note:</i> elements are identified by their HTML id, so if you don't see
 * your browser HTML display changing from the request, make sure you're using
 * the right id names in your response.
 * 
 * @package xajax
 */
class xajaxResponse
{
	/**#@+
	 * @access protected
	 */
	/**
	 * @var string internal XML storage
	 */	
	var $xml;
	/**
	 * @var string the encoding type to use
	 */
	var $sEncoding;
	/**
	 * @var boolean if special characters in the XML should be converted to
	 *              entities
	 */
	var $bOutputEntities;

	/**#@-*/
	
	/**
	 * The constructor's main job is to set the character encoding for the
	 * response.
	 * 
	 * <i>Note:</i> to change the character encoding for all of the
	 * responses, set the XAJAX_DEFAULT_ENCODING constant before you
	 * instantiate xajax.
	 * 
	 * @param string  contains the character encoding string to use
	 * @param boolean lets you set if you want special characters in the output
	 *                converted to HTML entities
	 * 
	 */
	function xajaxResponse($sEncoding=XAJAX_DEFAULT_CHAR_ENCODING, $bOutputEntities=false)
	{
		$this->setCharEncoding($sEncoding);
		$this->bOutputEntities = $bOutputEntities;
	}
	
	/**
	 * Sets the character encoding for the response based on $sEncoding, which
	 * is a string containing the character encoding to use. You don't need to
	 * use this method normally, since the character encoding for the response
	 * gets set automatically based on the XAJAX_DEFAULT_CHAR_ENCODING
	 * constant.
	 * 
	 * @param string
	 */
	function setCharEncoding($sEncoding)
	{
		$this->sEncoding = $sEncoding;
	}
	
	/**
	 * Tells the response object to convert special characters to HTML entities
	 * automatically (only works if the mb_string extension is available).
	 */
	function outputEntitiesOn()
	{
		$this->bOutputEntities = true;
	}
	
	/**
	 * Tells the response object to output special characters intact. (default
	 * behavior)
	 */
	function outputEntitiesOff()
	{
		$this->bOutputEntities = false;
	}

	/**
	 * Adds a confirm commands command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addConfirmCommands(1, "Do you want to preview the new data?");</kbd>
	 *
	 * @param integer the number of commands to skip if the user presses
	 *                Cancel in the browsers's confirm dialog
	 * @param string  the message to show in the browser's confirm dialog
	 */
	function addConfirmCommands($iCmdNumber, $sMessage)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"cc","t"=>$iCmdNumber),$sMessage);
	}
	
	/**
	 * Adds an assign command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addAssign("contentDiv", "innerHTML", "Some Text");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the part of the element you wish to modify ("innerHTML",
	 *               "value", etc.)
	 * @param string the data you want to set the attribute to
	 */
	function addAssign($sTarget,$sAttribute,$sData)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"as","t"=>$sTarget,"p"=>$sAttribute),$sData);
	}

	/**
	 * Adds an append command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addAppend("contentDiv", "innerHTML", "Some New Text");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the part of the element you wish to modify ("innerHTML",
	 *               "value", etc.)
	 * @param string the data you want to append to the end of the attribute
	 */
	function addAppend($sTarget,$sAttribute,$sData)
	{	
		$this->xml .= $this->_cmdXML(array("n"=>"ap","t"=>$sTarget,"p"=>$sAttribute),$sData);
	}

	/**
	 * Adds an prepend command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addPrepend("contentDiv", "innerHTML", "Some Starting Text");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the part of the element you wish to modify ("innerHTML",
	 *               "value", etc.)
	 * @param string the data you want to prepend to the beginning of the
	 *               attribute
	 */
	function addPrepend($sTarget,$sAttribute,$sData)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"pp","t"=>$sTarget,"p"=>$sAttribute),$sData);
	}

	/**
	 * Adds a replace command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addReplace("contentDiv", "innerHTML", "text", "<b>text</b>");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the part of the element you wish to modify ("innerHTML",
	 *               "value", etc.)
	 * @param string the string to search for
	 * @param string the string to replace the search string when found in the
	 *               attribute
	 */
	function addReplace($sTarget,$sAttribute,$sSearch,$sData)
	{
		$sDta = "<s><![CDATA[$sSearch]]></s><r><![CDATA[$sData]]></r>";
		$this->xml .= $this->_cmdXML(array("n"=>"rp","t"=>$sTarget,"p"=>$sAttribute),$sDta);
	}

	/**
	 * Adds a clear command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addClear("contentDiv", "innerHTML");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the part of the element you wish to clear ("innerHTML",
	 *               "value", etc.)
	 */	
	function addClear($sTarget,$sAttribute)
	{
		$this->addAssign($sTarget,$sAttribute,'');
	}
	
	/**
	 * Adds an alert command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addAlert("This is important information");</kbd>
	 * 
	 * @param string the text to be displayed in the Javascript alert box
	 */
	function addAlert($sMsg)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"al"),$sMsg);
	}

	/**
	 * Uses the addScript() method to add a Javascript redirect to another URL.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addRedirect("http://www.xajaxproject.org");</kbd>
	 * 
	 * @param string the URL to redirect the client browser to
	 */	
	function addRedirect($sURL)
	{
		//we need to parse the query part so that the values are rawurlencode()'ed
		//can't just use parse_url() cos we could be dealing with a relative URL which
		//  parse_url() can't deal with.
		$queryStart = strpos($sURL, '?', strrpos($sURL, '/'));
		if ($queryStart !== FALSE)
		{
			$queryStart++;
			$queryEnd = strpos($sURL, '#', $queryStart);
			if ($queryEnd === FALSE)
				$queryEnd = strlen($sURL);
			$queryPart = substr($sURL, $queryStart, $queryEnd-$queryStart);
			parse_str($queryPart, $queryParts);
			$newQueryPart = "";
			foreach($queryParts as $key => $value)
			{
				$newQueryPart .= rawurlencode($key).'='.rawurlencode($value).ini_get('arg_separator.output');
			}
			$sURL = str_replace($queryPart, $newQueryPart, $sURL);
		}
		$this->addScript('window.location = "'.$sURL.'";');
	}

	/**
	 * Adds a Javascript command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addScript("var x = prompt('get some text');");</kbd>
	 * 
	 * @param string contains Javascript code to be executed
	 */
	function addScript($sJS)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"js"),$sJS);
	}

	/**
	 * Adds a Javascript function call command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addScriptCall("myJSFunction", "arg 1", "arg 2", 12345);</kbd>
	 * 
	 * @param string $sFunc the name of a Javascript function
	 * @param mixed $args,... optional arguments to pass to the Javascript function
	 */
	function addScriptCall() {
		$arguments = func_get_args();
		$sFunc = array_shift($arguments);
		$sData = $this->_buildObjXml($arguments);
		$this->xml .= $this->_cmdXML(array("n"=>"jc","t"=>$sFunc),$sData);
	}

	/**
	 * Adds a remove element command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addRemove("Div2");</kbd>
	 * 
	 * @param string contains the id of an HTML element to be removed
	 */
	function addRemove($sTarget)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"rm","t"=>$sTarget),'');
	}

	/**
	 * Adds a create element command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addCreate("parentDiv", "h3", "myid");</kbd>
	 * 
	 * @param string contains the id of an HTML element to to which the new
	 *               element will be appended.
	 * @param string the tag to be added
	 * @param string the id to be assigned to the new element
	 * @param string deprecated, use the addCreateInput() method instead
	 */
	function addCreate($sParent, $sTag, $sId, $sType="")
	{
		if ($sType)
		{
			trigger_error("The \$sType parameter of addCreate has been deprecated.  Use the addCreateInput() method instead.", E_USER_WARNING);
			return;
		}
		$this->xml .= $this->_cmdXML(array("n"=>"ce","t"=>$sParent,"p"=>$sId),$sTag);
	}

	/**
	 * Adds a insert element command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addInsert("childDiv", "h3", "myid");</kbd>
	 * 
	 * @param string contains the id of the child before which the new element
	 *               will be inserted
	 * @param string the tag to be added
	 * @param string the id to be assigned to the new element
	 */
	function addInsert($sBefore, $sTag, $sId)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"ie","t"=>$sBefore,"p"=>$sId),$sTag);
	}

	/**
	 * Adds a insert element command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addInsertAfter("childDiv", "h3", "myid");</kbd>
	 * 
	 * @param string contains the id of the child after which the new element
	 *               will be inserted
	 * @param string the tag to be added
	 * @param string the id to be assigned to the new element
	 */
	function addInsertAfter($sAfter, $sTag, $sId)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"ia","t"=>$sAfter,"p"=>$sId),$sTag);
	}
	
	/**
	 * Adds a create input command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addCreateInput("form1", "text", "username", "input1");</kbd>
	 * 
	 * @param string contains the id of an HTML element to which the new input
	 *               will be appended
	 * @param string the type of input to be created (text, radio, checkbox,
	 *               etc.)
	 * @param string the name to be assigned to the new input and the variable
	 *               name when it is submitted
	 * @param string the id to be assigned to the new input
	 */
	function addCreateInput($sParent, $sType, $sName, $sId)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"ci","t"=>$sParent,"p"=>$sId,"c"=>$sType),$sName);
	}

	/**
	 * Adds an insert input command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addInsertInput("input5", "text", "username", "input1");</kbd>
	 * 
	 * @param string contains the id of the child before which the new element
	 *               will be inserted
	 * @param string the type of input to be created (text, radio, checkbox,
	 *               etc.)
	 * @param string the name to be assigned to the new input and the variable
	 *               name when it is submitted
	 * @param string the id to be assigned to the new input
	 */
	function addInsertInput($sBefore, $sType, $sName, $sId)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"ii","t"=>$sBefore,"p"=>$sId,"c"=>$sType),$sName);
	}

	/**
	 * Adds an insert input command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addInsertInputAfter("input7", "text", "email", "input2");</kbd>
	 * 
	 * @param string contains the id of the child after which the new element
	 *               will be inserted
	 * @param string the type of input to be created (text, radio, checkbox,
	 *               etc.)
	 * @param string the name to be assigned to the new input and the variable
	 *               name when it is submitted
	 * @param string the id to be assigned to the new input
	 */
	function addInsertInputAfter($sAfter, $sType, $sName, $sId)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"iia","t"=>$sAfter,"p"=>$sId,"c"=>$sType),$sName);
	}

	/**
	 * Adds an event command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addEvent("contentDiv", "onclick", "alert(\'Hello World\');");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the event you wish to set ("onclick", "onmouseover", etc.)
	 * @param string the Javascript string you want the event to invoke
	 */
	function addEvent($sTarget,$sEvent,$sScript)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"ev","t"=>$sTarget,"p"=>$sEvent),$sScript);
	}

	/**
	 * Adds a handler command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addHandler("contentDiv", "onclick", "content_click");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the event you wish to set ("onclick", "onmouseover", etc.)
	 * @param string the name of a Javascript function that will handle the
	 *               event. Multiple handlers can be added for the same event
	 */
	function addHandler($sTarget,$sEvent,$sHandler)
	{	
		$this->xml .= $this->_cmdXML(array("n"=>"ah","t"=>$sTarget,"p"=>$sEvent),$sHandler);
	}

	/**
	 * Adds a remove handler command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addRemoveHandler("contentDiv", "onclick", "content_click");</kbd>
	 * 
	 * @param string contains the id of an HTML element
	 * @param string the event you wish to remove ("onclick", "onmouseover",
	 *               etc.)
	 * @param string the name of a Javascript handler function that you want to
	 *               remove
	 */
	function addRemoveHandler($sTarget,$sEvent,$sHandler)
	{	
		$this->xml .= $this->_cmdXML(array("n"=>"rh","t"=>$sTarget,"p"=>$sEvent),$sHandler);
	}

	/**
	 * Adds an include script command message to the XML response.
	 * 
	 * <i>Usage:</i> <kbd>$objResponse->addIncludeScript("functions.js");</kbd>
	 * 
	 * @param string URL of the Javascript file to include
	 */
	function addIncludeScript($sFileName)
	{
		$this->xml .= $this->_cmdXML(array("n"=>"in"),$sFileName);
	}

	/**	
	 * Returns the XML to be returned from your function to the xajax processor
	 * on your page. Since xajax 0.2, you can also return an xajaxResponse
	 * object from your function directly, and xajax will automatically request
	 * the XML using this method call.
	 * 
	 * <i>Usage:</i> <kbd>return $objResponse->getXML();</kbd>
	 * 
	 * @return string response XML data
	 */
	function getXML()
	{
		$sXML = "<?xml version=\"1.0\"";
		if ($this->sEncoding && strlen(trim($this->sEncoding)) > 0)
			$sXML .= " encoding=\"".$this->sEncoding."\"";
		$sXML .= " ?"."><xjx>" . $this->xml . "</xjx>";
		
		return $sXML;
	}
	
	/**
	 * Adds the commands of the provided response XML output to this response
	 * object
	 * 
	 * <i>Usage:</i>
	 * <code>$r1 = $objResponse1->getXML();
	 * $objResponse2->loadXML($r1);
	 * return $objResponse2->getXML();</code>
	 * 
	 * @param string the response XML (returned from a getXML() method) to add
	 *               to the end of this response object
	 */
	function loadXML($mXML)
	{
		if (is_a($mXML, "xajaxResponse")) {
			$mXML = $mXML->getXML();
		}
		$sNewXML = "";
		$iStartPos = strpos($mXML, "<xjx>") + 5;
		$sNewXML = substr($mXML, $iStartPos);
		$iEndPos = strpos($sNewXML, "</xjx>");
		$sNewXML = substr($sNewXML, 0, $iEndPos);
		$this->xml .= $sNewXML;
	}

	/**
	 * Generates XML from command data
	 * 
	 * @access private
	 * @param array associative array of attributes
	 * @param string data
	 * @return string XML command
	 */
	function _cmdXML($aAttributes, $sData)
	{
		if ($this->bOutputEntities) {
			if (function_exists('mb_convert_encoding')) {
				$sData = call_user_func_array('mb_convert_encoding', array(&$sData, 'HTML-ENTITIES', $this->sEncoding));
			}
			else {
				trigger_error("The xajax XML response output could not be converted to HTML entities because the mb_convert_encoding function is not available", E_USER_NOTICE);
			}
		}
		$xml = "<cmd";
		foreach($aAttributes as $sAttribute => $sValue)
			$xml .= " $sAttribute=\"$sValue\"";
		if ($sData !== null && !stristr($sData,'<![CDATA['))
			$xml .= "><![CDATA[$sData]]></cmd>";
		else if ($sData !== null)
			$xml .= ">$sData</cmd>";
		else
			$xml .= "></cmd>";
		
		return $xml;
	}

	/**
	 * Recursively serializes a data structure in XML so it can be sent to
	 * the client. It could be thought of as the opposite of
	 * {@link xajax::_parseObjXml()}.
	 * 
	 * @access private
	 * @param mixed data structure to serialize to XML
	 * @return string serialized XML
	 */
	function _buildObjXml($var) {
		if (gettype($var) == "object") $var = get_object_vars($var);
		if (!is_array($var)) {
			return "<![CDATA[$var]]>";
		}
		else {
			$data = "<xjxobj>";
			foreach ($var as $key => $value) {
				$data .= "<e>";
				$data .= "<k>" . htmlspecialchars($key) . "</k>";
				$data .= "<v>" . $this->_buildObjXml($value) . "</v>";
				$data .= "</e>";
			}
			$data .= "</xjxobj>";
			return $data;
		}
	}
	
}// end class xajaxResponse
?>
