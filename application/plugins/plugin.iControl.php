<?php

namespace iControl;

/**
 * Abstract iControl plugin class
 */
abstract class Plugin_Name {
	/**
	 * Constants
	 */
	const VERSION = '0.1';

	/**
	 * Private properties
	 */
	private $iControl = null;

	/**
	 * Construct plugin
	 *
	 * @param object $iControl        	
	 */
	public function __construct($iControl) {
		$this->iControl = $iControl;
		
		error_log('Pugin v' . self::VERSION . ' ready!');
	}

	/**
	 * Perform actions during each loop
	 */
	public function loop() {
	}
}

?>
