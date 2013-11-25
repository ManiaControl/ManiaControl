<?php

namespace FML\Script\Sections;

/**
 * Script feature using functions
 *
 * @author steeffeen
 */
interface Functions {

	/**
	 * Return array of function implementations and signatures as keys
	 *
	 * @return array
	 */
	public function getFunctions();
}

?>
