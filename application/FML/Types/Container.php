<?php

namespace FML\Types;

/**
 * Interface for elements being able to contain other elements
 *
 * @author steeffeen
 */
interface Container {

	/**
	 * Add a new child
	 *
	 * @param Renderable $child        	
	 */
	public function add(Renderable $child);

	/**
	 * Remove all children
	 */
	public function removeChildren();
}

?>
