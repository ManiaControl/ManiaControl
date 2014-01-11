<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element installing a Replay
 *
 * @author steeffeen
 */
class InstallReplay implements Element {
	/**
	 * Protected Properties
	 */
	protected $tagName = 'install_replay';
	protected $name = '';
	protected $url = '';

	/**
	 * Construct a new InstallReplay Element
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
	 * @return \FML\ManiaCode\InstallReplay
	 */
	public function setName($name) {
		$this->name = (string) $name;
		return $this;
	}

	/**
	 * Set the Url of the Replay
	 *
	 * @param string $url Replay Url
	 * @return \FML\ManiaCode\InstallReplay
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
