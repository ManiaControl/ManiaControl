<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer\Xmlrpc;

if(extension_loaded('xmlrpc'))
{
	abstract class Request
	{
		/**
		 * @param string $method
		 * @param mixed[] $args
		 * @return string
		 */
		static function encode($method, $args)
		{
			return xmlrpc_encode_request($method, $args, array('encoding' => 'utf-8', 'verbosity' => 'no_white_space'));
		}

		/**
		 * @param string $message
		 * @return mixed
		 * @throws ParseException
		 */
		static function decode($message)
		{
			$value = xmlrpc_decode_request($message, $method, 'utf-8');
			if($value === null)
				throw new ParseException();

			if($method === null)
			{
				if(@xmlrpc_is_fault($value))
					return array('fault', $value);
				return array('response', $value);
			}
			return array('call', array($method, $value));
		}
	}
}
else
{
	abstract class Request
	{
		const DATE_FORMAT = 'Ymd\TH:i:s';

		/**
		 * @param string $method
		 * @param mixed[] $args
		 * @return string
		 */
		static function encode($method, $args)
		{
			$xml = '<?xml version="1.0" encoding="utf-8"?><methodCall><methodName>'.$method.'</methodName><params>';
			foreach($args as $arg)
				$xml .= '<param><value>'.self::encodeValue($arg).'</value></param>';
			$xml .= '</params></methodCall>';
			return $xml;
		}

		/**
		 * @param mixed $v
		 * @return string
		 */
		private static function encodeValue($v)
		{
			switch(gettype($v))
			{
				case 'boolean':
					return '<boolean>'.((int) $v).'</boolean>';
				case 'integer':
					return '<int>'.$v.'</int>';
				case 'double':
					return '<double>'.$v.'</double>';
				case 'string':
					return '<string>'.htmlspecialchars($v).'</string>';
				case 'object':
					if($v instanceof Base64)
						return '<base64>'.base64_encode($v->scalar).'</base64>';
					if($v instanceof \DateTime)
						return '<dateTime.iso8601>'.$v->format(self::DATE_FORMAT).'</dateTime.iso8601>';
					$v = get_object_vars($v);
					// fallthrough
				case 'array':
					$return = '';
					// pure array case
					if(array_keys($v) == range(0, count($v) - 1))
					{
						foreach($v as $item)
							$return .= '<value>'.self::encodeValue($item).'</value>';
						return '<array><data>'.$return.'</data></array>';
					}
					// else it's a struct
					foreach($v as $name => $value)
						$return .= '<member><name>'.$name.'</name><value>'.self::encodeValue($value).'</value></member>';
					return '<struct>'.$return.'</struct>';
			}
			return '';
		}

		/**
		 * @param string $message
		 * @return mixed
		 * @throws ParseException
		 */
		static function decode($message)
		{
			$xml = @simplexml_load_string($message);
			if(!$xml)
				throw new ParseException();

			if($xml->getName() == 'methodResponse')
			{
				if($xml->fault)
					return array('fault', self::decodeValue($xml->fault->value));
				return array('response', self::decodeValue($xml->params->param->value));
			}
			$params = array();
			foreach($xml->params->param as $param)
				$params[] = self::decodeValue($param->value);
			return array('call', array((string) $xml->methodName, $params));
		}

		/**
		 * @param \SimpleXMLElement $elt
		 * @return mixed
		 */
		private static function decodeValue($elt)
		{
			$elt = $elt->children();
			$elt = $elt[0];
			switch($elt->getName())
			{
				case 'boolean':
					return (bool) $elt;
				case 'i4':
				case 'int':
					return (int) $elt;
				case 'double':
					return (double) $elt;
				case 'string':
					return (string) $elt;
				case 'base64':
					return new Base64(base64_decode($elt));
				case 'dateTime.iso8601':
					return \DateTime::createFromFormat(self::DATE_FORMAT, (string) $elt);
				case 'array':
					$arr = array();
					foreach($elt->data->value as $v)
						$arr[] = self::decodeValue($v);
					return $arr;
				case 'struct':
					$struct = array();
					foreach($elt as $member)
						$struct[(string) $member->name] = self::decodeValue($member->value);
					return $struct;
			}
		}
	}
}

class ParseException extends Exception {}

?>
