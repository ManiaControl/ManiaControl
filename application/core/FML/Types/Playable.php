<?php

namespace FML\Types;

/**
 * Interface for Elements with Media Attributes
 *
 * @author steeffeen
 */
interface Playable {

	/**
	 * Set Data
	 *
	 * @param string $data Media Url
	 */
	public function setData($data);

	/**
	 * Set Data Id to use from the Dico
	 * 
	 * @param string $dataId
	 */
	public function setDataId($dataId);

	/**
	 * Set Play
	 *
	 * @param bool $play Whether the Control should start playing automatically
	 */
	public function setPlay($play);

	/**
	 * Set Looping
	 *
	 * @param bool $looping Whether the Control should play looping
	 */
	public function setLooping($looping);

	/**
	 * Set Music
	 *
	 * @param bool $music Whether the Control represents Background Music
	 */
	public function setMusic($music);

	/**
	 * Set Volume
	 *
	 * @param float $volume Media Volume
	 */
	public function setVolume($volume);
}
