<?php

namespace FML;

/**
 * Class representing a Custom_UI
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CustomUI {
	/*
	 * Protected properties
	 */
	protected $encoding = 'utf-8';
	protected $tagName = 'custom_ui';
	protected $noticeVisible = null;
	protected $challengeInfoVisible = null;
	protected $netInfosVisible = null;
	protected $chatVisible = null;
	protected $checkpointListVisible = null;
	protected $roundScoresVisible = null;
	protected $scoretableVisible = null;
	protected $globalVisible = null;

	/**
	 * Create a new CustomUI object
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
	public function setXMLEncoding($encoding) {
		$this->encoding = (string)$encoding;
		return $this;
	}

	/**
	 * Set showing of notices
	 *
	 * @param bool $visible Whether notices should be shown
	 * @return static
	 */
	public function setNoticeVisible($visible) {
		$this->noticeVisible = $visible;
		return $this;
	}

	/**
	 * Set showing of the challenge info
	 *
	 * @param bool $visible Whether the challenge info should be shown
	 * @return static
	 */
	public function setChallengeInfoVisible($visible) {
		$this->challengeInfoVisible = $visible;
		return $this;
	}

	/**
	 * Set showing of the net infos
	 *
	 * @param bool $visible Whether the net infos should be shown
	 * @return static
	 */
	public function setNetInfosVisible($visible) {
		$this->netInfosVisible = $visible;
		return $this;
	}

	/**
	 * Set showing of the chat
	 *
	 * @param bool $visible Whether the chat should be shown
	 * @return static
	 */
	public function setChatVisible($visible) {
		$this->chatVisible = $visible;
		return $this;
	}

	/**
	 * Set showing of the checkpoint list
	 *
	 * @param bool $visible Whether the checkpoint should be shown
	 * @return static
	 */
	public function setCheckpointListVisible($visible) {
		$this->checkpointListVisible = $visible;
		return $this;
	}

	/**
	 * Set showing of round scores
	 *
	 * @param bool $visible Whether the round scores should be shown
	 * @return static
	 */
	public function setRoundScoresVisible($visible) {
		$this->roundScoresVisible = $visible;
		return $this;
	}

	/**
	 * Set showing of the scoretable
	 *
	 * @param bool $visible Whether the scoretable should be shown
	 * @return static
	 */
	public function setScoretableVisible($visible) {
		$this->scoretableVisible = $visible;
		return $this;
	}

	/**
	 * Set global showing
	 *
	 * @param bool $visible Whether the UI should be disabled completely
	 * @return static
	 */
	public function setGlobalVisible($visible) {
		$this->globalVisible = $visible;
		return $this;
	}

	/**
	 * Render the XML document
	 *
	 * @param \DOMDocument $domDocument (optional) DOMDocument for which the XML element should be rendered
	 * @return \DOMDocument
	 */
	public function render($domDocument = null) {
		$isChild = (bool)$domDocument;
		if (!$isChild) {
			$domDocument                = new \DOMDocument('1.0', $this->encoding);
			$domDocument->xmlStandalone = true;
		}
		$xmlElement = $domDocument->createElement($this->tagName);
		if (!$isChild) {
			$domDocument->appendChild($xmlElement);
		}
		$settings = $this->getSettings();
		foreach ($settings as $setting => $value) {
			if (is_null($value)) {
				continue;
			}
			$xmlSubElement = $domDocument->createElement($setting);
			$xmlSubElement->setAttribute('visible', ($value ? 1 : 0));
			$xmlElement->appendChild($xmlSubElement);
		}
		if ($isChild) {
			return $xmlElement;
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

	/**
	 * Get associative array of all CustomUI settings
	 *
	 * @return array
	 */
	protected function getSettings() {
		$settings                    = array();
		$settings['challenge_info']  = $this->challengeInfoVisible;
		$settings['chat']            = $this->chatVisible;
		$settings['checkpoint_list'] = $this->checkpointListVisible;
		$settings['global']          = $this->globalVisible;
		$settings['net_infos']       = $this->netInfosVisible;
		$settings['notice']          = $this->noticeVisible;
		$settings['round_scores']    = $this->roundScoresVisible;
		$settings['scoretable']      = $this->scoretableVisible;
		return $settings;
	}
}
