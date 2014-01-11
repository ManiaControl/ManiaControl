<?php

namespace FML\Controls;

use FML\Types\Playable;
use FML\Types\Scriptable;

/**
 * Video Element
 * (CMlMediaPlayer)
 *
 * @author steeffeen
 */
class Video extends Control implements Playable, Scriptable {
	/**
	 * Protected Properties
	 */
	protected $data = '';
	protected $play = 0;
	protected $looping = 0;
	protected $music = 0;
	protected $volume = 1.;
	protected $scriptEvents = 0;

	/**
	 * Construct a new Video Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'video';
	}

	/**
	 *
	 * @see \FML\Types\Playable::setData()
	 * @return \FML\Controls\Video
	 */
	public function setData($data) {
		$this->data = (string) $data;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Playable::setPlay()
	 * @return \FML\Controls\Video
	 */
	public function setPlay($play) {
		$this->play = ($play ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Playable::setLooping()
	 * @return \FML\Controls\Video
	 */
	public function setLooping($looping) {
		$this->looping = ($looping ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Playable::setMusic()
	 * @return \FML\Controls\Video
	 */
	public function setMusic($music) {
		$this->music = ($music ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Playable::setVolume()
	 * @return \FML\Controls\Video
	 */
	public function setVolume($volume) {
		$this->volume = (float) $volume;
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Scriptable::setScriptEvents()
	 * @return \FML\Controls\Video
	 */
	public function setScriptEvents($scriptEvents) {
		$this->scriptEvents = ($scriptEvents ? 1 : 0);
		return $this;
	}

	/**
	 *
	 * @see \FML\Control::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->data) {
			$xmlElement->setAttribute('data', $this->data);
		}
		if ($this->play) {
			$xmlElement->setAttribute('play', $this->play);
		}
		if ($this->looping) {
			$xmlElement->setAttribute('looping', $this->looping);
		}
		if ($this->music) {
			$xmlElement->setAttribute('music', $this->music);
		}
		if ($this->volume != 1.) {
			$xmlElement->setAttribute('volume', $this->volume);
		}
		if ($this->scriptEvents) {
			$xmlElement->setAttribute('scriptevents', $this->scriptEvents);
		}
		return $xmlElement;
	}
}
