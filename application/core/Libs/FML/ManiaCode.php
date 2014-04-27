<?php

namespace FML;

use FML\ManiaCode\AddBuddy;
use FML\ManiaCode\AddFavorite;
use FML\ManiaCode\Element;
use FML\ManiaCode\GetSkin;
use FML\ManiaCode\Go_To;
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
use FML\ManiaCode\InstallMacroblock;

/**
 * Class representing a ManiaCode
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaCode {
	/*
	 * Protected Properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'maniacode';
	protected $noConfirmation = null;
	protected $elements = array();

	/**
	 * Create a new ManiaCode Object
	 *
	 * @return \FML\ManiaCode
	 */
	public static function create() {
		$maniaCode = new ManiaCode();
		return $maniaCode;
	}

	/**
	 * Construct a new ManiaCode Object
	 */
	public function __construct() {
	}

	/**
	 * Set XML Encoding
	 *
	 * @param string $encoding XML Encoding
	 * @return \FML\ManiaCode
	 */
	public function setXmlEncoding($encoding) {
		$this->encoding = (string) $encoding;
		return $this;
	}

	/**
	 * Disable the Showing of the Confirmation at the End of the ManiaCode
	 *
	 * @param bool $disable Whether the Confirmation should be shown
	 * @return \FML\ManiaCode
	 */
	public function disableConfirmation($disable) {
		$this->noConfirmation = ($disable ? 1 : 0);
		return $this;
	}

	/**
	 * Show a Message
	 *
	 * @param string $message Message Text
	 * @return \FML\ManiaCode
	 */
	public function addShowMessage($message) {
		$messageElement = new ShowMessage($message);
		$this->addElement($messageElement);
		return $this;
	}

	/**
	 * Install a Macroblock
	 *
	 * @param string $name Macroblock Name
	 * @param string $file Macroblock File
	 * @param string $url Macroblock Url
	 * @return \FML\ManiaCode
	 */
	public function addInstallMacroblock($name, $file, $url) {
		$macroblockElement = new InstallMacroblock($name, $file, $url);
		$this->addElement($macroblockElement);
		return $this;
	}

	/**
	 * Install a Map
	 *
	 * @param string $name Map Name
	 * @param string $url Map Url
	 * @return \FML\ManiaCode
	 */
	public function addInstallMap($name, $url) {
		$mapElement = new InstallMap($name, $url);
		$this->addElement($mapElement);
		return $this;
	}

	/**
	 * Play a Map
	 *
	 * @param string $name Map Name
	 * @param string $url Map Url
	 * @return \FML\ManiaCode
	 */
	public function addPlayMap($name, $url) {
		$mapElement = new PlayMap($name, $url);
		$this->addElement($mapElement);
		return $this;
	}

	/**
	 * Install a Replay
	 *
	 * @param string $name Replay Name
	 * @param string $url Replay Url
	 * @return \FML\ManiaCode
	 */
	public function addInstallReplay($name, $url) {
		$replayElement = new InstallReplay($name, $url);
		$this->addElement($replayElement);
		return $this;
	}

	/**
	 * View a Replay
	 *
	 * @param string $name Replay Name
	 * @param string $url Replay Url
	 * @return \FML\ManiaCode
	 */
	public function addViewReplay($name, $url) {
		$replayElement = new ViewReplay($name, $url);
		$this->addElement($replayElement);
		return $this;
	}

	/**
	 * Play a Replay
	 *
	 * @param string $name Replay Name
	 * @param string $url Replay Url
	 * @return \FML\ManiaCode
	 */
	public function addPlayReplay($name, $url) {
		$replayElement = new PlayReplay($name, $url);
		$this->addElement($replayElement);
		return $this;
	}

	/**
	 * Install a Skin
	 *
	 * @param string $name Skin Name
	 * @param string $file Skin File
	 * @param string $url Skin Url
	 * @return \FML\ManiaCode
	 */
	public function addInstallSkin($name, $file, $url) {
		$skinElement = new InstallSkin($name, $file, $url);
		$this->addElement($skinElement);
		return $this;
	}

	/**
	 * Get a Skin
	 *
	 * @param string $name Skin Name
	 * @param string $file Skin File
	 * @param string $url Skin Url
	 * @return \FML\ManiaCode
	 */
	public function addGetSkin($name, $file, $url) {
		$skinElement = new GetSkin($name, $file, $url);
		$this->addElement($skinElement);
		return $this;
	}

	/**
	 * Add a Buddy
	 *
	 * @param string $login Buddy Login
	 * @return \FML\ManiaCode
	 */
	public function addAddBuddy($login) {
		$buddyElement = new AddBuddy($login);
		$this->addElement($buddyElement);
		return $this;
	}

	/**
	 * Go to a Link
	 *
	 * @param string $link Goto Link
	 * @return \FML\ManiaCode
	 */
	public function addGoto($link) {
		$gotoElement = new Go_To($link);
		$this->addElement($gotoElement);
		return $this;
	}

	/**
	 * Join a Server
	 *
	 * @param string $login Server Login
	 * @return \FML\ManiaCode
	 */
	public function addJoinServer($login) {
		$serverElement = new JoinServer($login);
		$this->addElement($serverElement);
		return $this;
	}

	/**
	 * Add a Server as Favorite
	 *
	 * @param string $login Server Login
	 * @return \FML\ManiaCode
	 */
	public function addAddFavorite($login) {
		$favoriteElement = new AddFavorite($login);
		$this->addElement($favoriteElement);
		return $this;
	}

	/**
	 * Install a Script
	 *
	 * @param string $name Script Name
	 * @param string $file Script File
	 * @param string $url Script Url
	 * @return \FML\ManiaCode
	 */
	public function addInstallScript($name, $file, $url) {
		$scriptElement = new InstallScript($name, $file, $url);
		$this->addElement($scriptElement);
		return $this;
	}

	/**
	 * Install a Title Pack
	 *
	 * @param string $name Pack Name
	 * @param string $file Pack File
	 * @param string $url Pack Url
	 * @return \FML\ManiaCode
	 */
	public function addInstallPack($name, $file, $url) {
		$packElement = new InstallPack($name, $file, $url);
		$this->addElement($packElement);
		return $this;
	}

	/**
	 * Add a ManiaCode Element
	 *
	 * @param Element $element The Element to add
	 * @return \FML\ManiaCode
	 */
	public function addElement(Element $element) {
		array_push($this->elements, $element);
		return $this;
	}

	/**
	 * Removes all Elements from the ManiaCode
	 *
	 * @return \FML\ManiaCode
	 */
	public function removeElements() {
		$this->elements = array();
		return $this;
	}

	/**
	 * Render the XML Document
	 *
	 * @param bool (optional) $echo Whether the XML Text should be echoed and the Content-Type Header should be set
	 * @return \DOMDocument
	 */
	public function render($echo = false) {
		$domDocument = new \DOMDocument('1.0', $this->encoding);
		$domDocument->xmlStandalone = true;
		$maniaCode = $domDocument->createElement($this->tagName);
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
	 * Get String Representation
	 *
	 * @return string
	 */
	public function __toString() {
		$domDocument = $this->render();
		$xmlText = $domDocument->saveXML();
		return $xmlText;
	}
}
