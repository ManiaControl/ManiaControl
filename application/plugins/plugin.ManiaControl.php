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
	private $mControl = null;

	/**
	 * Construct plugin
	 *
	 * @param object $mControl        	
	 */
	public function __construct($mControl) {
		$this->mControl = $mControl;
		
		error_log('Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Perform actions during each loop
	 */
	public function loop() {
	}
}

?>
