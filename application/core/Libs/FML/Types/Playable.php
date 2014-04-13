<?php

namespace FML\Types;

/**
 * Interface for Elements with Media Attributes
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Playable {

	/**
	 * Set Data
	 *
	 * @param string $data Media Url
	 * @return \FML\Types\Playable
	 */
	public function setData($data);

	/**
	 * Set Data Id to use from the Dico
	 * 
	 * @param string $dataId
	 * @return \FML\Types\Playable
	 */
	public function setDataId($dataId);

	/**
	 * Set Play
	 *
	 * @param bool $play Whether the Control should start playing automatically
	 * @return \FML\Types\Playable
	 */
	public function setPlay($play);

	/**
	 * Set Looping
	 *
	 * @param bool $looping Whether the Control should play looping
	 * @return \FML\Types\Playable
	 */
	public function setLooping($looping);

	/**
	 * Set Music
	 *
	 * @param bool $music Whether the Control represents Background Music
	 * @return \FML\Types\Playable
	 */
	public function setMusic($music);

	/**
	 * Set Volume
	 *
	 * @param float $volume Media Volume
	 * @return \FML\Types\Playable
	 */
	public function setVolume($volume);
}
