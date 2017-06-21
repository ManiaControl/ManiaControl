<?php

namespace FML;

/**
 * Class representing a Custom_UI
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CustomUI
{

    /**
     * @var bool $globalVisible If the UI should be shown at all
     */
    protected $globalVisible = null;

    /**
     * @var bool $challengeInfoVisible If the challenge info should be shown
     */
    protected $challengeInfoVisible = null;

    /**
     * @var bool $chatVisible If the chat should be shown
     */
    protected $chatVisible = null;

    /**
     * @var bool $checkpointListVisible If the checkpoint list should be shown
     */
    protected $checkpointListVisible = null;

    /**
     * @var bool $netInfosVisible If the net infos should be shown
     */
    protected $netInfosVisible = null;

    /**
     * @var bool $noticeVisible If notices should be shown
     */
    protected $noticeVisible = null;

    /**
     * @var bool $roundScoresVisible If round scores should be shown
     */
    protected $roundScoresVisible = null;

    /**
     * @var bool $scoretableVisible If the score table should be shown
     */
    protected $scoretableVisible = null;

    /**
     * Create a new Custom UI
     *
     * @api
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get global visibility
     *
     * @api
     * @return bool
     */
    public function getGlobalVisible()
    {
        return $this->globalVisible;
    }

    /**
     * Set global visibility
     *
     * @api
     * @param bool $visible If the UI should be shown at all
     * @return static
     */
    public function setGlobalVisible($visible)
    {
        $this->globalVisible = $visible;
        return $this;
    }

    /**
     * Get showing of the challenge info
     *
     * @api
     * @return bool
     */
    public function getChallengeInfoVisible()
    {
        return $this->challengeInfoVisible;
    }

    /**
     * Set showing of the challenge info
     *
     * @api
     * @param bool $visible If the challenge info should be shown
     * @return static
     */
    public function setChallengeInfoVisible($visible)
    {
        $this->challengeInfoVisible = $visible;
        return $this;
    }

    /**
     * Get showing of the chat
     *
     * @api
     * @return bool
     */
    public function getChatVisible()
    {
        return $this->chatVisible;
    }

    /**
     * Set showing of the chat
     *
     * @api
     * @param bool $visible If the chat should be shown
     * @return static
     */
    public function setChatVisible($visible)
    {
        $this->chatVisible = $visible;
        return $this;
    }

    /**
     * Get showing of the checkpoint list
     *
     * @api
     * @return bool
     */
    public function getCheckpointListVisible()
    {
        return $this->checkpointListVisible;
    }

    /**
     * Set showing of the checkpoint list
     *
     * @api
     * @param bool $visible If the checkpoint list should be shown
     * @return static
     */
    public function setCheckpointListVisible($visible)
    {
        $this->checkpointListVisible = $visible;
        return $this;
    }

    /**
     * Get showing of the net infos
     *
     * @api
     * @return bool
     */
    public function getNetInfosVisible()
    {
        return $this->netInfosVisible;
    }

    /**
     * Set showing of the net infos
     *
     * @api
     * @param bool $visible If the net infos should be shown
     * @return static
     */
    public function setNetInfosVisible($visible)
    {
        $this->netInfosVisible = $visible;
        return $this;
    }

    /**
     * Get showing of notices
     *
     * @api
     * @return bool
     */
    public function getNoticeVisible()
    {
        return $this->noticeVisible;
    }

    /**
     * Set showing of notices
     *
     * @api
     * @param bool $visible If notices should be shown
     * @return static
     */
    public function setNoticeVisible($visible)
    {
        $this->noticeVisible = $visible;
        return $this;
    }

    /**
     * Get showing of round scores
     *
     * @api
     * @return bool
     */
    public function getRoundScoresVisible()
    {
        return $this->roundScoresVisible;
    }

    /**
     * Set showing of round scores
     *
     * @api
     * @param bool $visible If round scores should be shown
     * @return static
     */
    public function setRoundScoresVisible($visible)
    {
        $this->roundScoresVisible = $visible;
        return $this;
    }

    /**
     * Get showing of the score table
     *
     * @api
     * @return bool
     */
    public function getScoretableVisible()
    {
        return $this->scoretableVisible;
    }

    /**
     * Set showing of the score table
     *
     * @api
     * @param bool $visible If the score table should be shown
     * @return static
     */
    public function setScoretableVisible($visible)
    {
        $this->scoretableVisible = $visible;
        return $this;
    }

    /**
     * Render the Custom UI standalone
     *
     * @return \DOMDocument
     */
    public function renderStandalone()
    {
        $domDocument                = new \DOMDocument("1.0", "utf-8");
        $domDocument->xmlStandalone = true;

        $domElement = $this->render($domDocument);
        $domDocument->appendChild($domElement);

        return $domDocument;
    }

    /**
     * Render the Custom UI
     *
     * @param \DOMDocument $domDocument DOMDocument for which the Custom UI should be rendered
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("custom_ui");

        $settings = $this->getSettings();
        foreach ($settings as $setting => $value) {
            if ($value === null) {
                continue;
            }

            $settingDomElement = $domDocument->createElement($setting);
            $settingDomElement->setAttribute("visible", ($value ? 1 : 0));
            $domElement->appendChild($settingDomElement);
        }

        return $domElement;
    }

    /**
     * Get string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->renderStandalone()
                    ->saveXML();
    }

    /**
     * Get associative array of all Custom UI settings
     *
     * @return array
     */
    protected function getSettings()
    {
        return array(
            "global" => $this->globalVisible,
            "challenge_info" => $this->challengeInfoVisible,
            "chat" => $this->chatVisible,
            "checkpoint_list" => $this->checkpointListVisible,
            "net_infos" => $this->netInfosVisible,
            "notice" => $this->noticeVisible,
            "round_scores" => $this->roundScoresVisible,
            "scoretable" => $this->scoretableVisible
        );
    }

}
