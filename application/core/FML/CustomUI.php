<?php

namespace FML;

/**
 * Class representing a Custom_UI
 *
 * @author steeffeen
 */
class CustomUI {
	
	/**
	 * Protected Properties
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
	 * Set XML Encoding
	 *
	 * @param string $encoding
	 * @return \FML\CustomUI
	 */
	public function setXMLEncoding($encoding) {
		$this->encoding = $encoding;
		return $this;
	}

	/**
	 * Set Showing of Notices
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setNoticeVisible($visible) {
		$this->noticeVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Challenge Info
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setChallengeInfoVisible($visible) {
		$this->challengeInfoVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Net Infos
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setNetInfosVisible($visible) {
		$this->netInfosVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Chat
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setChatVisible($visible) {
		$this->chatVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Checkpoint List
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setCheckpointListVisible($visible) {
		$this->checkpointListVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of Round Scores
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setRoundScoresVisible($visible) {
		$this->roundScoresVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Scoretable
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setScoretableVisible($visible) {
		$this->scoretableVisible = $visible;
		return $this;
	}

	/**
	 * Set Global Showing
	 *
	 * @param bool $visible
	 * @return \FML\CustomUI
	 */
	public function setGlobalVisible($visible) {
		$this->globalVisible = $visible;
		return $this;
	}

	/**
	 * Render the XML Document
	 *
	 * @param \DOMDocument $domDocument
	 * @return \DOMDocument
	 */
	public function render($domDocument = null) {
		$isChild = false;
		if ($domDocument) {
			$isChild = true;
		}
		if (!$isChild) {
			$domDocument = new \DOMDocument('1.0', $this->encoding);
		}
		$xmlElement = $domDocument->createElement($this->tagName);
		$domDocument->appendChild($xmlElement);
		$settings = $this->getSettings();
		foreach ($settings as $setting => $value) {
			if ($value === null) continue;
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
	 * Get String Representation
	 *
	 * @return string
	 */
	public function __toString() {
		$domDocument = $this->render();
		$xmlText = $domDocument->saveXML();
		return $xmlText;
	}

	/**
	 * Get associative Array of all CustomUI Settings
	 *
	 * @return array
	 */
	private function getSettings() {
		$settings = array();
		$settings['challenge_info'] = $this->challengeInfoVisible;
		$settings['chat'] = $this->chatVisible;
		$settings['checkpoint_list'] = $this->checkpointListVisible;
		$settings['global'] = $this->globalVisible;
		$settings['net_infos'] = $this->netInfosVisible;
		$settings['notice'] = $this->noticeVisible;
		$settings['round_scores'] = $this->roundScoresVisible;
		$settings['scoretable'] = $this->scoretableVisible;
		return $settings;
	}
}