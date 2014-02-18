<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

class Message 
{
	public $message;
	public $messageType;  // methodCall / methodResponse / fault
	public $faultCode;
	public $faultString;
	public $methodName;
	public $params;
	// Current variable stacks
	protected $arrayStructs = array();  // Stack to keep track of the current array/struct
	protected $arrayStructsTypes = array();  // Stack to keep track of whether things are structs or array
	protected $currentStructName = array();  // A stack as well
	protected $param;
	protected $value;
	protected $currentTag;
	protected $currentTagContents;
	// The XML parser
	protected $parser;

	function __construct ($message) 
	{
		$this->message = $message;
	}

	function parse() 
	{
		
		// first remove the XML declaration
		$this->message = preg_replace('/<\?xml(.*)?\?'.'>/', '', $this->message);
		if (trim($this->message) == '') 
		{
			return false;
		}
		$this->parser = xml_parser_create();
		// Set XML parser to take the case of tags into account
		xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
		// Set XML parser callback functions
		xml_set_object($this->parser, $this);
		xml_set_element_handler($this->parser, 'tag_open', 'tag_close');
		xml_set_character_data_handler($this->parser, 'cdata');
		if (!xml_parse($this->parser, $this->message)) 
		{
			/* die(sprintf('GbxRemote XML error: %s at line %d',
			               xml_error_string(xml_get_error_code($this->_parser)),
			               xml_get_current_line_number($this->_parser))); */
			return false;
		}
		xml_parser_free($this->parser);
		// Grab the error messages, if any
		if ($this->messageType == 'fault') 
		{
			$this->faultCode = $this->params[0]['faultCode'];
			$this->faultString = $this->params[0]['faultString'];
		}
		return true;
	}

	function tag_open($parser, $tag, $attr) 
	{
		$this->currentTag = $tag;
		switch ($tag) 
		{
			case 'methodCall':
			case 'methodResponse':
			case 'fault':
				$this->messageType = $tag;
				break;
			// Deal with stacks of arrays and structs
			case 'data':  // data is to all intents and purposes more interesting than array
				$this->arrayStructsTypes[] = 'array';
				$this->arrayStructs[] = array();
				break;
			case 'struct':
				$this->arrayStructsTypes[] = 'struct';
				$this->arrayStructs[] = array();
				break;
		}
	}

	function cdata($parser, $cdata) 
	{
		$this->currentTagContents .= $cdata;
	}

	function tag_close($parser, $tag) 
	{
		$valueFlag = false;
		switch ($tag) 
		{
			case 'int':
			case 'i4':
				$value = (int)trim($this->currentTagContents);
				$this->currentTagContents = '';
				$valueFlag = true;
				break;
			case 'double':
				$value = (double)trim($this->currentTagContents);
				$this->currentTagContents = '';
				$valueFlag = true;
				break;
			case 'string':
				$value = (string)trim($this->currentTagContents);
				$this->currentTagContents = '';
				$valueFlag = true;
				break;
			case 'dateTime.iso8601':
				$value = new Date(trim($this->currentTagContents));
				// $value = $iso->getTimestamp();
				$this->currentTagContents = '';
				$valueFlag = true;
				break;
			case 'value':
				// If no type is indicated, the type is string
				if (trim($this->currentTagContents) != '') {
					$value = (string)$this->currentTagContents;
					$this->currentTagContents = '';
					$valueFlag = true;
				}
				break;
			case 'boolean':
				$value = (boolean)trim($this->currentTagContents);
				$this->currentTagContents = '';
				$valueFlag = true;
				break;
			case 'base64':
				$value = base64_decode($this->currentTagContents);
				$this->currentTagContents = '';
				$valueFlag = true;
				break;
				// Deal with stacks of arrays and structs
			case 'data':
			case 'struct':
				$value = array_pop($this->arrayStructs);
				array_pop($this->arrayStructsTypes);
				$valueFlag = true;
				break;
			case 'member':
				array_pop($this->currentStructName);
				break;
			case 'name':
				$this->currentStructName[] = trim($this->currentTagContents);
				$this->currentTagContents = '';
				break;
			case 'methodName':
				$this->methodName = trim($this->currentTagContents);
				$this->currentTagContents = '';
				break;
		}

		if ($valueFlag) 
		{
			/*
			if (!is_array($value) && !is_object($value)) {
				$value = trim($value);
			}
			*/
			if (count($this->arrayStructs) > 0) 
			{
				// Add value to struct or array
				if ($this->arrayStructsTypes[count($this->arrayStructsTypes)-1] == 'struct') 
				{
					// Add to struct
					$this->arrayStructs[count($this->arrayStructs)-1][$this->currentStructName[count($this->currentStructName)-1]] = $value;
				} 
				else 
				{
					// Add to array
					$this->arrayStructs[count($this->arrayStructs)-1][] = $value;
				}
			} 
			else 
			{
				// Just add as a paramater
				$this->params[] = $value;
			}
		}
	}
}

?>