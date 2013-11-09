<?php

namespace ManiaControl;

/**
 * Abstract ManiaControl plugin class
 */
abstract class Plugin_Name {
	/**
	 * Constants
	 */
	const VERSION = '0.1';

	/**
	 * Private properties
	 */
	private $mc = null;

	/**
	 * Construct plugin
	 *
	 * @param object $mc        	
	 */
	public function __construct($mc) {
		$this->mc = $mc;
		
		error_log('Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Perform actions during each loop
	 */
	public function loop() {
	}
}

?>
