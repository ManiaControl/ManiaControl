<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

class Value 
{
	public $data;
	public $type;

	function __construct($data, $type = false) 
	{
		$this->data = $data;
		if (!$type) 
		{
			$type = $this->calculateType();
		}
		$this->type = $type;
		if ($type == 'struct') 
		{
			// Turn all the values in the array into new Value objects
			foreach ($this->data as $key => $value) 
			{
				$this->data[$key] = new Value($value);
			}
		}
		if ($type == 'array') 
		{
			for ($i = 0, $j = count($this->data); $i < $j; $i++) 
			{
				$this->data[$i] = new Value($this->data[$i]);
			}
		}
	}

	function calculateType() 
	{
		if ($this->data === true || $this->data === false) 
		{
			return 'boolean';
		}
		if (is_integer($this->data)) 
		{
			return 'int';
		}
		if (is_double($this->data)) 
		{
			return 'double';
		}
		// Deal with IXR object types base64 and date
		if (is_object($this->data) && $this->data instanceof Date) 
		{
			return 'date';
		}
		if (is_object($this->data) && $this->data instanceof Base64) 
		{
			return 'base64';
		}
		// If it is a normal PHP object convert it into a struct
		if (is_object($this->data)) 
		{
			$this->data = get_object_vars($this->data);
			return 'struct';
		}
		if (!is_array($this->data)) 
		{
			return 'string';
		}
		// We have an array - is it an array or a struct?
		if ($this->isStruct($this->data)) 
		{
			return 'struct';
		} 
		else 
		{
			return 'array';
		}
	}

	function getXml() 
	{
		// Return XML for this value
		switch ($this->type) 
		{
			case 'boolean':
				return '<boolean>' . ($this->data ? '1' : '0') . '</boolean>';
				break;
			case 'int':
				return '<int>' . $this->data . '</int>';
				break;
			case 'double':
				return '<double>' . $this->data . '</double>';
				break;
			case 'string':
				return '<string>' . htmlspecialchars($this->data) . '</string>';
				break;
			case 'array':
				$return = '<array><data>' . LF;
				foreach ($this->data as $item) 
				{
					$return .= '	<value>' . $item->getXml() . '</value>' . LF;
				}
				$return .= '</data></array>';
				return $return;
				break;
			case 'struct':
				$return = '<struct>' . LF;
				foreach ($this->data as $name => $value) 
				{
					$return .= '	<member><name>' . $name . '</name><value>';
					$return .= $value->getXml() . '</value></member>' . LF;
				}
				$return .= '</struct>';
				return $return;
				break;
			case 'date':
			case 'base64':
				return $this->data->getXml();
				break;
		}
		return false;
	}

	function isStruct($array) 
	{
		// Nasty function to check if an array is a struct or not
		$expected = 0;
		foreach ($array as $key => $value) 
		{
			if ((string)$key != (string)$expected) 
			{
				return true;
			}
			$expected++;
		}
		return false;
	}
}

?>