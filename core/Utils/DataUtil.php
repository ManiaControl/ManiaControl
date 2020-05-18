<?php

namespace ManiaControl\Utils;

use InvalidArgumentException;

/**
 * Utility Class offering Methods related to Data Structures
 *
 * @author    axelalex2
 * @copyright 
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class DataUtil {

	/**
	 * Build a multi-dimensional array into XML-String.
	 *
	 * @param  array  $a
	 * @param  string $root
	 * @return string
	 */
	public static function buildXmlStandaloneFromArray(array $a, $root = '') {
		$domDocument                = new \DOMDocument("1.0", "utf-8");
		$domDocument->xmlStandalone = true;

		if ($root === '') {	
			if (count($a) != 1) {
				throw new InvalidArgumentException('Array needs to have a single root!');
			}

			reset($a);
			$root = key($a);
			if (!is_string($root)) {
				throw new InvalidArgumentException('All keys have to be strings!');
			}
			$a = $a[$root];
		}

		$domRootElement = $domDocument->createElement($root);
		$domDocument->appendChild($domRootElement);
		self::buildXmlChildFromArray($domDocument, $domRootElement, $a);
		return $domDocument->saveXML();
	}

	/**
	 * Build a multi-dimensional array into XML-String (recursion for children).
	 *
	 * @param \DOMDocument $domDocument
	 * @param \DOMElement  &$domElement
	 * @param array        $a
	 */
	private static function buildXmlChildFromArray(\DOMDocument $domDocument, \DOMElement &$domElement, array $a) {
		foreach ($a as $key => $value) {
			if (is_array($value)) {
				$domSubElement = $domDocument->createElement($key);
				$domElement->appendChild($domSubElement);
				self::buildXmlChildFromArray($domDocument, $domSubElement, $a[$key]);
			} else {
				$valueString = (is_string($value) ? $value : var_export($value, true));
				$domElement->setAttribute($key, $valueString);
			}
		}
	}

	/**
	 * Checks, if a string ends with the given substring.
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	/**
	 * Checks, if a string starts with the given substring.
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	/**
	 * Implodes sub-arrays with position properties.
	 *
	 * @param array $a
	 * @param bool  $recurse
	 * @return array
	 */
	public static function implodePositions(array $a, $recurse = true) {
		$result = array();
		foreach ($a as $key => $value) {
			if (is_array($value)) {
				$arrayKeys = array_keys($value);
				if (in_array('x', $arrayKeys) && in_array('y', $arrayKeys)) {
					$value = $value['x'].' '.$value['y'].(in_array('z', $arrayKeys) ? ' '.$value['z'] : '');
				} elseif ($recurse) {
					$value = self::implodePositions($value, $recurse);
				}
			}

			$result[$key] = $value;
		}
		return $result;
	}

	/**
	 * Transforms a multidimensional-array into a 1-dimensional with concatenated keys.
	 *
	 * @param array  $a
	 * @param string $delimiter
	 * @param string $prefix (used for recursion)
	 * @return array
	 */
	public static function flattenArray(array $a, $delimiter = '.', $prefix = '') {
		$result = array();
		foreach ($a as $key => $value)
		{
			if (!is_string($key)) {
				throw new InvalidArgumentException('All keys have to be strings!');
			}

			$new_key = $prefix . (empty($prefix) ? '' : $delimiter) . $key;
			if (is_array($value)) {
				$result = array_merge($result, self::flattenArray($value, $delimiter, $new_key));
			} else {
				$result[$new_key] = $value;
			}
		}

		return $result;
	}

	/**
	 * Transforms a 1-dimensional array into a multi-dimensional by splitting the keys by a given delimiter.
	 *
	 * @param array  $a
	 * @param string $delimiter
	 * @return array
	 */
	public static function unflattenArray(array $a, $delimiter = '.') {
		$result = array();
		foreach ($a as $key => $value) {
			if (!is_string($key)) {
				throw new InvalidArgumentException('All keys have to be strings!');
			}

			$keySplits = explode($delimiter, $key);
			$numSplits = count($keySplits);
			$subResult = &$result;
			for ($i = 0; $i < $numSplits; $i++) {
				$keySplit = $keySplits[$i];
				if ($i < $numSplits-1) {
					// subarray
					if (!array_key_exists($keySplit, $subResult)) {
						$subResult[$keySplit] = array();
					}
					if (!is_array($subResult[$keySplit])) {
						throw new InvalidArgumentException('');
					} else {
						$subResult = &$subResult[$keySplit];
					}
				} else {
					// insert value
					if (array_key_exists($keySplit, $subResult)) {
						throw new InvalidArgumentException('Found duplicated key!');
					} else {
						$subResult[$keySplit] = $value;
					}
				}
			}
		}
		return $result;
	}
}
