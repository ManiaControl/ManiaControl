<?php

namespace FML\Script\Features;

use FML\Controls\Label;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptInclude;
use FML\Script\ScriptLabel;

/**
 * Script Feature showing the current time on a Label
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Clock extends ScriptFeature
{

    /**
     * @var Label $label Clock Label
     */
    protected $label = null;

    /**
     * @var bool $showSeconds Show the seconds
     */
    protected $showSeconds = null;

    /**
     * @var bool $showFullDate Show the date
     */
    protected $showFullDate = null;

    /**
     * Construct a new Clock
     *
     * @api
     * @param Label $label        (optional) Clock Label
     * @param bool  $showSeconds  (optional) Show the seconds
     * @param bool  $showFullDate (optional) Show the date
     */
    public function __construct(Label $label = null, $showSeconds = true, $showFullDate = false)
    {
        if ($label) {
            $this->setLabel($label);
        }
        $this->setShowSeconds($showSeconds)
             ->setShowFullDate($showFullDate);
    }

    /**
     * Get the Label
     *
     * @api
     * @return Label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the Label
     *
     * @api
     * @param Label $label Clock Label
     * @return static
     */
    public function setLabel(Label $label)
    {
        $label->checkId();
        $this->label = $label;
        return $this;
    }

    /**
     * Get if seconds should be shown
     *
     * @api
     * @return bool
     */
    public function getShowSeconds()
    {
        return $this->showSeconds;
    }

    /**
     * Set if seconds should be shown
     *
     * @api
     * @param bool $showSeconds If seconds should be shown
     * @return static
     */
    public function setShowSeconds($showSeconds)
    {
        $this->showSeconds = (bool)$showSeconds;
        return $this;
    }

    /**
     * Get if the full date should be shown
     *
     * @api
     * @return bool
     */
    public function getShowFullDate()
    {
        return $this->showFullDate;
    }

    /**
     * Set if the full date should be shown
     *
     * @api
     * @param bool $showFullDate If the full date should be shown
     * @return static
     */
    public function setShowFullDate($showFullDate)
    {
        $this->showFullDate = (bool)$showFullDate;
        return $this;
    }

    /**
     * @see ScriptFeature::prepare()
     */
    public function prepare(Script $script)
    {
        $script->setScriptInclude(ScriptInclude::TEXTLIB)
               ->appendGenericScriptLabel(ScriptLabel::TICK, $this->getScriptText(), true);
        return $this;
    }

    /**
     * Get the script text
     *
     * @return string
     */
    protected function getScriptText()
    {
        $controlId  = Builder::escapeText($this->label->getId());
        $scriptText = "
declare ClockLabel <=> (Page.GetFirstChild({$controlId}) as CMlLabel);
declare TimeText = CurrentLocalDateText;";
        if (!$this->showSeconds) {
            $scriptText .= "
TimeText = TextLib::SubText(TimeText, 0, 16);";
        }
        if (!$this->showFullDate) {
            $scriptText .= "
TimeText = TextLib::SubText(TimeText, 11, 9);";
        }
        $scriptText .= "
ClockLabel.Value = TimeText;";
        return $scriptText;
    }

}
