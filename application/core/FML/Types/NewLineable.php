<?php

namespace FML\Types;

/**
 * Interface for elements with AutoNewLine attribute
 *
 * @author steeffeen
 */
interface NewLineable {

	/**
	 * Set auto new line
	 *
	 * @param bool $autoNewLine        	
	 */
	public function setAutoNewLine($autoNewLine);
}
