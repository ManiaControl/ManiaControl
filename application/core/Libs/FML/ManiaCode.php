<?php

namespace FML;

use FML\ManiaCode\AddBuddy;
use FML\ManiaCode\AddFavorite;
use FML\ManiaCode\Element;
use FML\ManiaCode\GetSkin;
use FML\ManiaCode\Go_To;
use FML\ManiaCode\InstallMacroblock;
use FML\ManiaCode\InstallMap;
use FML\ManiaCode\InstallPack;
use FML\ManiaCode\InstallReplay;
use FML\ManiaCode\InstallScript;
use FML\ManiaCode\InstallSkin;
use FML\ManiaCode\JoinServer;
use FML\ManiaCode\PlayMap;
use FML\ManiaCode\PlayReplay;
use FML\ManiaCode\ShowMessage;
use FML\ManiaCode\ViewReplay;

/**
 * Class representing a ManiaCode
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaCode {
	/*
	 * Protected properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'maniacode';
	protected $noConfirmation = null;
	/** @var Element[] $elements */
	protected $elements = array();

	/**
	 * Create a new ManiaCode object
	 *
	 * @return static
	 */
	public static function create() {
		return new static();
	}

	/**
	 * Set XML encoding
	 *
	 * @param string $encoding XML encoding
	 * @return static
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = (string)$encoding;
		return $this;
	}

	/**
	 * Disable the showing of the confirmation at the end of the ManiaCode
	 *
	 * @param bool $disable Whether the confirmation should be shown
	 * @return static
	 */
	public function disableConfirmation($disable) {
		$this->noConfirmation = ($disable ? 1 : 0);
		return $this;
	}

	/**
	 * Show a message
	 *
	 * @param string $message Message text
	 * @return static
	 */
	public function addShowMessage($message) {
		$messageElement = new ShowMessage($message);
		$this->addElement($messageElement);
		return $this;
	}

	/**
	 * Install a Macroblock
	 *
	 * @param string $name Macroblock name
	 * @param string $file Macroblock file
	 * @param string $url  Macroblock url
	 * @return static
	 */
	public function addInstallMacroblock($name, $file, $url) {
		$macroblockElement = new InstallMacroblock($name, $file, $url);
		$this->addElement($macroblockElement);
		return $this;
	}

	/**
	 * Install a map
	 *
	 * @param string $name Map name
	 * @param string $url  Map url
	 * @return static
	 */
	public function addInstallMap($name, $url) {
		$mapElement = new InstallMap($name, $url);
		$this->addElement($mapElement);
		return $this;
	}

	/**
	 * Play a map
	 *
	 * @param string $name Map name
	 * @param string $url  Map url
	 * @return static
	 */
	public function addPlayMap($name, $url) {
		$mapElement = new PlayMap($name, $url);
		$this->addElement($mapElement);
		return $this;
	}

	/**
	 * Install a replay
	 *
	 * @param string $name Replay name
	 * @param string $url  Replay url
	 * @return static
	 */
	public function addInstallReplay($name, $url) {
		$replayElement = new InstallReplay($name, $url);
		$this->addElement($replayElement);
		return $this;
	}

	/**
	 * View a replay
	 *
	 * @param string $name Replay name
	 * @param string $url  Replay url
	 * @return static
	 */
	public function addViewReplay($name, $url) {
		$replayElement = new ViewReplay($name, $url);
		$this->addElement($replayElement);
		return $this;
	}

	/**
	 * Play a replay
	 *
	 * @param string $name Replay name
	 * @param string $url  Replay url
	 * @return static
	 */
	public function addPlayReplay($name, $url) {
		$replayElement = new PlayReplay($name, $url);
		$this->addElement($replayElement);
		return $this;
	}

	/**
	 * Install a skin
	 *
	 * @param string $name Skin name
	 * @param string $file Skin file
	 * @param string $url  Skin url
	 * @return static
	 */
	public function addInstallSkin($name, $file, $url) {
		$skinElement = new InstallSkin($name, $file, $url);
		$this->addElement($skinElement);
		return $this;
	}

	/**
	 * Get a skin
	 *
	 * @param string $name Skin name
	 * @param string $file Skin file
	 * @param string $url  Skin url
	 * @return static
	 */
	public function addGetSkin($name, $file, $url) {
		$skinElement = new GetSkin($name, $file, $url);
		$this->addElement($skinElement);
		return $this;
	}

	/**
	 * Add a buddy
	 *
	 * @param string $login Buddy login
	 * @return static
	 */
	public function addAddBuddy($login) {
		$buddyElement = new AddBuddy($login);
		$this->addElement($buddyElement);
		return $this;
	}

	/**
	 * Go to a link
	 *
	 * @param string $link Goto link
	 * @return static
	 */
	public function addGoto($link) {
		$gotoElement = new Go_To($link);
		$this->addElement($gotoElement);
		return $this;
	}

	/**
	 * Join a server
	 *
	 * @param string $login Server login
	 * @return static
	 */
	public function addJoinServer($login) {
		$serverElement = new JoinServer($login);
		$this->addElement($serverElement);
		return $this;
	}

	/**
	 * Add a server as favorite
	 *
	 * @param string $login Server login
	 * @return static
	 */
	public function addAddFavorite($login) {
		$favoriteElement = new AddFavorite($login);
		$this->addElement($favoriteElement);
		return $this;
	}

	/**
	 * Install a script
	 *
	 * @param string $name Script name
	 * @param string $file Script file
	 * @param string $url  Script url
	 * @return static
	 */
	public function addInstallScript($name, $file, $url) {
		$scriptElement = new InstallScript($name, $file, $url);
		$this->addElement($scriptElement);
		return $this;
	}

	/**
	 * Install a title pack
	 *
	 * @param string $name Pack name
	 * @param string $file Pack file
	 * @param string $url  Pack url
	 * @return static
	 */
	public function addInstallPack($name, $file, $url) {
		$packElement = new InstallPack($name, $file, $url);
		$this->addElement($packElement);
		return $this;
	}

	/**
	 * Add a ManiaCode element
	 *
	 * @param Element $element Element to add
	 * @return static
	 */
	public function addElement(Element $element) {
		array_push($this->elements, $element);
		return $this;
	}

	/**
	 * Remove all elements from the ManiaCode
	 *
	 * @return static
	 */
	public function removeElements() {
		$this->elements = array();
		return $this;
	}

	/**
	 * Render the XML document
	 *
	 * @param bool $echo (optional) Whether the XML text should be echoed and the Content-Type header should be set
	 * @return \DOMDocument
	 */
	public function render($echo = false) {
		$domDocument                = new \DOMDocument('1.0', $this->encoding);
		$domDocument->xmlStandalone = true;
		$maniaCode                  = $domDocument->createElement($this->tagName);
		$domDocument->appendChild($maniaCode);
		if ($this->noConfirmation) {
			$maniaCode->setAttribute('noconfirmation', $this->noConfirmation);
		}
		foreach ($this->elements as $element) {
			$xmlElement = $element->render($domDocument);
			$maniaCode->appendChild($xmlElement);
		}
		if ($echo) {
			header('Content-Type: application/xml; charset=utf-8;');
			echo $domDocument->saveXML();
		}
		return $domDocument;
	}

	/**
	 * Get string representation
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->render()->saveXML();
	}
}
