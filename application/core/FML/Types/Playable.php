<?php

namespace FML\Types;

/**
 * Interface for elements with media attributes
 *
 * @author steeffeen
 */
interface Playable {

	/**
	 * Set Data
	 *
	 * @param string $data
	 *        	Media Url
	 */
	public function setData($data);

	/**
	 * Set Play
	 *
	 * @param bool $play
	 *        	If the Control should start playing automatically
	 */
	public function setPlay($play);

	/**
	 * Set Looping
	 *
	 * @param bool $looping
	 *        	If the Control should playback looping
	 */
	public function setLooping($looping);

	/**
	 * Set Music
	 *
	 * @param bool $music
	 *        	If the Control is Background Music
	 */
	public function setMusic($music);

	/**
	 * Set Volume
	 *
	 * @param float $volume
	 *        	Control Volume
	 */
	public function setVolume($volume);
}
