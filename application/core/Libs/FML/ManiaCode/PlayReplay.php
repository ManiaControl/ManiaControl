<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element playing a Replay
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayReplay implements Element {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'play_replay';
	protected $name = '';
	protected $url = '';

	/**
	 * Create a new PlayReplay Element
	 *
	 * @param string $name (optional) Replay Name
	 * @param string $url (optional) Replay Url
	 * @return \FML\ManiaCode\PlayReplay
	 */
	public static function create($name = null, $url = null) {
		$playReplay = new PlayReplay($name, $url);
		return $playReplay;
	}

	/**
	 * Construct a new PlayReplay Element
	 *
	 * @param string $name (optional) Replay Name
	 * @param string $url (optional) Replay Url
	 */
	public function __construct($name = null, $url = null) {
		if ($name !== null) {
			$this->setName($name);
		}
		if ($url !== null) {
			$this->setUrl($url);
		}
	}

	/**
	 * Set the Name of the Replay
	 *
	 * @param string $name Replay Name
	 * @return \FML\ManiaCode\PlayReplay
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the Url of the Replay
	 *
	 * @param string $url Replay Url
	 * @return \FML\ManiaCode\PlayReplay
	 */
	public function setUrl($url) {
		$this->url = (string) $url;
		return $this;
	}

	/**
	 *
	 * @see \FML\ManiaCode\Element::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		$nameElement = $domDocument->createElement('name', $this->name);
		$xmlElement->appendChild($nameElement);
		$urlElement = $domDocument->createElement('url', $this->url);
		$xmlElement->appendChild($urlElement);
		return $xmlElement;
	}
}
