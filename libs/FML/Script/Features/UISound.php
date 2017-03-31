<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for playing a UISound
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UISound extends ScriptFeature
{

    /*
     * Constants
     */
    const Bonus            = "Bonus";
    const Capture          = "Capture";
    const Checkpoint       = "Checkpoint";
    const Combo            = "Combo";
    const Custom1          = "Custom1";
    const Custom2          = "Custom2";
    const Custom3          = "Custom3";
    const Custom4          = "Custom4";
    const Default_         = "Default";
    const EndMatch         = "EndMatch";
    const EndRound         = "EndRound";
    const Finish           = "Finish";
    const FirstHit         = "FirstHit";
    const Notice           = "Notice";
    const PhaseChange      = "PhaseChange";
    const PlayerEliminated = "PlayerEliminated";
    const PlayerHit        = "PlayerHit";
    const PlayersRemaining = "PlayersRemaining";
    const RankChange       = "RankChange";
    const Record           = "Record";
    const ScoreProgress    = "ScoreProgress";
    const Silence          = "Silence";
    const StartMatch       = "StartMatch";
    const StartRound       = "StartRound";
    const TieBreakPoint    = "TieBreakPoint";
    const TiePoint         = "TiePoint";
    const TimeOut          = "TimeOut";
    const VictoryPoint     = "VictoryPoint";
    const Warning          = "Warning";

    /**
     * @var string $soundName Sound name
     */
    protected $soundName = null;

    /**
     * @var Control $control Sound Control
     */
    protected $control = null;

    /**
     * @var int $variant Sound variant
     */
    protected $variant = 0;

    /**
     * @var string $labelName Script Label name
     */
    protected $labelName = null;

    /**
     * @var float $volume Volume
     */
    protected $volume = 1.;

    /**
     * Construct a new UISound
     *
     * @api
     * @param string  $soundName (optional) Sound name
     * @param Control $control   (optional) Sound Control
     * @param int     $variant   (optional) Sound variant
     * @param string  $labelName (optional) Script Label name
     */
    public function __construct($soundName = null, Control $control = null, $variant = 0, $labelName = ScriptLabel::MOUSECLICK)
    {
        if ($soundName) {
            $this->setSoundName($soundName);
        }
        if ($control) {
            $this->setControl($control);
        }
        if ($variant) {
            $this->setVariant($variant);
        }
        if ($labelName) {
            $this->setLabelName($labelName);
        }
    }

    /**
     * Get the sound to play
     *
     * @api
     * @return string
     */
    public function getSoundName()
    {
        return $this->soundName;
    }

    /**
     * Set the sound to play
     *
     * @api
     * @param string $soundName Sound name
     * @return static
     */
    public function setSoundName($soundName)
    {
        $this->soundName = (string)$soundName;
        return $this;
    }

    /**
     * Get the sound Control
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the sound Control
     *
     * @api
     * @param Control $control (optional) Sound Control
     * @return static
     */
    public function setControl(Control $control = null)
    {
        if ($control) {
            $control->checkId();
            if ($control instanceof Scriptable) {
                $control->setScriptEvents(true);
            }
        }
        $this->control = $control;
        return $this;
    }

    /**
     * Get the sound variant
     *
     * @api
     * @return int
     */
    public function getVariant()
    {
        return $this->variant;
    }

    /**
     * Set the sound variant
     *
     * @api
     * @param int $variant Sound variant
     * @return static
     */
    public function setVariant($variant)
    {
        $this->variant = (int)$variant;
        return $this;
    }

    /**
     * Get the Script Label name
     *
     * @api
     * @return string
     */
    public function getLabelName()
    {
        return $this->labelName;
    }

    /**
     * Set the Script Label name
     *
     * @api
     * @param string $labelName Script Label name
     * @return static
     */
    public function setLabelName($labelName)
    {
        $this->labelName = (string)$labelName;
        return $this;
    }

    /**
     * Get the volume
     *
     * @api
     * @return float
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set the volume
     *
     * @api
     * @param float $volume Sound volume
     * @return static
     */
    public function setVolume($volume)
    {
        $this->volume = (float)$volume;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->appendGenericScriptLabel($this->labelName, $this->getScriptText());
        return $this;
    }

    /**
     * Get the script text
     *
     * @return string
     */
    protected function getScriptText()
    {
        if ($this->control) {
            // Control event
            $controlId = Builder::escapeText($this->control->getId());
            return "
if (Event.Control.ControlId == {$controlId}) {
	PlayUiSound(CMlScriptIngame::EUISound::{$this->soundName}, {$this->variant}, {$this->volume});
}";
        }

        // Other events
        return "
PlayUiSound(CMlScriptIngame::EUISound::{$this->soundName}, {$this->variant}, {$this->volume});";
    }

}
