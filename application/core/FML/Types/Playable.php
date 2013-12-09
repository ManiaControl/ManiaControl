<?php

namespace FML\Types;

/**
 * Interface for elements with media attributes
 *
 * @author steeffeen
 */
interface Playable {

	/**
	 * Set data
	 *
	 * @param string $data        	
	 */
	public function setData($data);

	/**
	 * Set play
	 *
	 * @param bool $play        	
	 */
	public function setPlay($play);

	/**
	 * Set looping
	 *
	 * @param bool $looping        	
	 */
	public function setLooping($looping);

	/**
	 * Set music
	 *
	 * @param bool $music        	
	 */
	public function setMusic($music);

	/**
	 * Set volume
	 *
	 * @param float $volume        	
	 */
	public function setVolume($volume);
}
