<?php

namespace ManiaControl;

/**
 * Class offering methods to format times
 *
 * @author Steff
 */
class TimeFormatter {

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
	 * @param int $time        	
	 * @return string
	 */
	public static function formatTimeH($time) {
		return gmdate("H:i:s", $seconds);
	}

	/**
	 * Convert the given time (seconds) to mysql timestamp
	 *
	 * @param int $time        	
	 * @return string
	 */
	public static function formatTimestamp($time) {
		return date("Y-m-d H:i:s", $time);
	}
}

?>
