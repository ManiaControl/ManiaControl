<?php

namespace FML\Script\Features;

use FML\Controls\Control;
use FML\Script\Builder;
use FML\Script\Script;
use FML\Script\ScriptLabel;
use FML\Types\Scriptable;

/**
 * Script Feature for opening a player profile
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class PlayerProfile extends ScriptFeature
{

    /**
     * @var string $login Player login
     */
    protected $login = null;

    /**
     * @var Control $control Profile Control
     */
    protected $control = null;

    /**
     * @var string $labelName Script Label name
     */
    protected $labelName = null;

    /**
     * Construct a new Player Profile
     *
     * @api
     * @param string  $login     (optional) Player login
     * @param Control $control   (optional) Profile Control
     * @param string  $labelName (optional) Script Label name
     */
    public function __construct($login = null, Control $control = null, $labelName = ScriptLabel::MOUSECLICK)
    {
        if ($login) {
            $this->setLogin($login);
        }
        if ($control) {
            $this->setControl($control);
        }
        if ($labelName) {
            $this->setLabelName($labelName);
        }
    }

    /**
     * Get the login of the opened player
     *
     * @api
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set the login of the opened player
     *
     * @api
     * @param string $login Player login
     * @return static
     */
    public function setLogin($login)
    {
        $this->login = (string)$login;
        return $this;
    }

    /**
     * Get the Profile Control
     *
     * @api
     * @return Control
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Set the Profile Control
     *
     * @api
     * @param Control $control Profile Control
     * @return static
     */
    public function setControl(Control $control)
    {
        $control->checkId();
        if ($control instanceof Scriptable) {
            $control->setScriptEvents(true);
        }
        $this->control = $control;
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
        $login = Builder::escapeText($this->login);

        if ($this->control) {
            // Control event
            $controlId = Builder::escapeText($this->control->getId());
            return "
if (Event.Control.ControlId == {$controlId}) {
	ShowProfile({$login});
}";
        }

        // Other events
        return "
ShowProfile({$login});";
    }

}
