<?php

namespace FML\Types;

/**
 * Interface for elements being able to contain other elements
 *
 * @author steeffeen
 */
interface Container {

	/**
	 * Add a new Child
	 *
	 * @param Renderable $child
	 *        	The child to add
	 */
	public function add(Renderable $child);

	/**
	 * Remove all Children
	 */
	public function removeChildren();
}
