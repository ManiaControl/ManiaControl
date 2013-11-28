<?php

namespace FML\Types;

/**
 * Interface for elements with media attributes
 *
 * @author steeffeen
 */
interface Playable {
	/**
	 * Protected properties
	 */
	protected $data = '';
	protected $play = 0;
	protected $looping = 0;
	protected $music = 1;
	protected $volume = 1.;

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

?>
