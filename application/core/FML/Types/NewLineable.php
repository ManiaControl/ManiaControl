<?php

namespace FML\Types;

/**
 * Interface for elements with AutoNewLine attribute
 *
 * @author steeffeen
 */
interface NewLineable {

	/**
	 * Set Auto New Line
	 *
	 * @param bool $autoNewLine
	 *        	If the Control should insert New Lines automatically
	 */
	public function setAutoNewLine($autoNewLine);
}
