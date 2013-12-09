<?php

namespace FML\Script\Sections;

/**
 * Script feature using includes
 *
 * @author steeffeen
 */
interface Includes {

	/**
	 * Return array of included files with namespaces as keys
	 *
	 * @return array
	 */
	public function getIncludes();
}
