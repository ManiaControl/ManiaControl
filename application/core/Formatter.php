<?php

namespace ManiaControl;

/**
 * Class offering methods to format texts and values
 *
 * @author steeffeen & kremsy
 */
abstract class Formatter {

	/**
	 * Formats the given time (milliseconds)
	 *
	 * @param int $time        	
	 * @return string
	 */
	public static function formatTime($time) {
		if (!is_int($time)) {
			$time = (int) $time;
		}
		$milliseconds = $time % 1000;
		$seconds = floor($time / 1000);
		$minutes = floor($seconds / 60);
		$hours = floor($minutes / 60);
		$minutes -= $hours * 60;
		$seconds -= $hours * 60 + $minutes * 60;
		$format = ($hours > 0 ? $hours . ':' : '');
		$format .= ($hours > 0 && $minutes < 10 ? '0' : '') . $minutes . ':';
		$format .= ($seconds < 10 ? '0' : '') . $seconds . ':';
		$format .= ($milliseconds < 100 ? '0' : '') . ($milliseconds < 10 ? '0' : '') . $milliseconds;
		return $format;
	}

	/**
	 * Formats the given time (seconds) to hh:mm:ss
	 *
	 * @param int $seconds        	
	 * @return string
	 */
	public static function formatTimeH($seconds) {
		return gmdate("H:i:s", $seconds);
	}

	/**
	 * Convert the given time (seconds) to mysql timestamp
	 *
	 * @param int $seconds        	
	 * @return string
	 */
	public static function formatTimestamp($seconds) {
		return date("Y-m-d H:i:s", $seconds);
	}

	/**
	 * Strip $codes from the string
	 *
	 * @param string $string        	
	 * @return string
	 */
	public static function stripCodes($string) {
		$string = preg_replace('/(?<!\$)((?:\$\$)*)\$[^$0-9a-hlp]/iu', '$1', $string);
		$string = self::stripLinks($string);
		$string = self::stripColors($string);
		return $string;
	}

	/**
	 * Remove link codes from the string
	 *
	 * @param string $string        	
	 * @return string
	 */
	public static function stripLinks($string) {
		return preg_replace('/(?<!\$)((?:\$\$)*)\$[hlp](?:\[.*?\])?(.*?)(?:\$[hlp]|(\$z)|$)/iu', '$1$2$3', $string);
	}

	/**
	 * Remove colors from the string
	 *
	 * @param string $string        	
	 * @return string
	 */
	static function stripColors($string) {
		return preg_replace('/(?<!\$)((?:\$\$)*)\$(?:g|[0-9a-f][^\$]{0,2})/iu', '$1', $string);
	}
}
