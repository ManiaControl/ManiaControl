<?php

namespace FML\Types;

/**
 * Interface for Elements with AutoNewLine Attribute
 *
 * @author steeffeen
 */
interface NewLineable {

	/**
	 * Set Auto New Line
	 *
	 * @param bool $autoNewLine Whether the Control should insert New Lines automatically
	 * @return \FML\Types\NewLineable
	 */
	public function setAutoNewLine($autoNewLine);
}
