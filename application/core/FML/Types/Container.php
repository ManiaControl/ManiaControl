<?php

namespace FML\Types;

/**
 * Interface for Elements being able to contain other Elements
 *
 * @author steeffeen
 */
interface Container {

	/**
	 * Add a new Child
	 *
	 * @param Renderable $child The Child Element to add
	 */
	public function add(Renderable $child);

	/**
	 * Remove all Children
	 */
	public function removeChildren();
}
