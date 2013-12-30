<?php

namespace FML\Elements;

/**
 * Class representing the Custom_UI
 *
 * @author steeffeen
 */
class CustomUI implements Renderable {
	/**
	 * Protected Properties
	 */
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
	 * Set Showing of Notices
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setNoticeVisible($visible) {
		$this->noticeVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Challenge Info
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setChallengeInfoVisible($visible) {
		$this->challengeInfoVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Net Infos
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setNetInfosVisible($visible) {
		$this->netInfosVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Chat
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setChatVisible($visible) {
		$this->chatVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Checkpoint List
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setCheckpointListVisible($visible) {
		$this->checkpointListVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of Round Scores
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setRoundScoresVisible($visible) {
		$this->roundScoresVisible = $visible;
		return $this;
	}

	/**
	 * Set Showing of the Scoretable
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setScoretableVisible($visible) {
		$this->scoretableVisible = $visible;
		return $this;
	}

	/**
	 * Set Global Showing
	 *
	 * @param bool $visible        	
	 * @return \FML\Elements\CustomUI
	 */
	public function setGlobalVisible($visible) {
		$this->globalVisible = $visible;
		return $this;
	}

	/**
	 *
	 * @see \FML\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$settings = $this->getSettings();
		$xml = $domDocument->createElement($this->tagName);
		foreach ($settings as $setting => $value) {
			if ($value === null) continue;
			$xmlElement = $domDocument->createElement($setting);
			$xmlElement->setAttribute('visible', ($value ? 1 : 0));
			$xml->appendChild($xmlElement);
		}
		return $xml;
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
