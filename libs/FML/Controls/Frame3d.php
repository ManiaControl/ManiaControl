<?php

namespace FML\Controls;

use FML\Stylesheet\Style3d;
use FML\Types\Scriptable;

/**
 * Frame3d Control
 * (CMlFrame)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Frame3d extends Frame implements Scriptable
{

    /*
     * Constants
     */
    const STYLE_BaseStation = "BaseStation";
    const STYLE_BaseBoxCase = "BaseBoxCase";
    const STYLE_TitleLogo   = "TitleLogo";
    /**
     * @deprecated Use STYLE_TitleLogo
     * @see        Frame3d::STYLE_TitleLogo
     */
    const STYLE_Titlelogo   = "Titlelogo";
    const STYLE_ButtonBack  = "ButtonBack";
    const STYLE_ButtonNav   = "ButtonNav";
    const STYLE_ButtonH     = "ButtonH";
    const STYLE_Station3x3  = "Station3x3";
    const STYLE_Title       = "Title";
    const STYLE_TitleEditor = "TitleEditor";
    const STYLE_Window      = "Window";

    /**
     * @var string $style3dId Style3d id
     */
    protected $style3dId = null;

    /**
     * @var Style3d $style3d Style3d
     */
    protected $style3d = null;

    /**
     * @var bool $scriptEvents Script events usage
     */
    protected $scriptEvents = null;

    /**
     * @var string[] $scriptActionParameters Script action parameters
     */
    protected $scriptActionParameters = null;

    /**
     * @var string $scriptAction Script action
     */
    protected $scriptAction = null;

    /**
     * Get the Style3d id
     *
     * @api
     * @return string
     */
    public function getStyle3dId()
    {
        return $this->style3dId;
    }

    /**
     * Set the Style3d id
     *
     * @api
     * @param string $style3dId Style3d id
     * @return static
     */
    public function setStyle3dId($style3dId)
    {
        $this->style3dId = (string)$style3dId;
        $this->style3d   = null;
        return $this;
    }

    /**
     * Get the Style3d
     *
     * @api
     * @return Style3d
     */
    public function getStyle3d()
    {
        return $this->style3d;
    }

    /**
     * Set the Style3d
     *
     * @api
     * @param Style3d $style3d Style3d
     * @return static
     */
    public function setStyle3d(Style3d $style3d)
    {
        $this->style3dId = null;
        $this->style3d   = $style3d;
        return $this;
    }

    /**
     * @see Scriptable::getScriptEvents()
     */
    public function getScriptEvents()
    {
        return $this->scriptEvents;
    }

    /**
     * @see Scriptable::setScriptEvents()
     */
    public function setScriptEvents($scriptEvents)
    {
        $this->scriptEvents = (bool)$scriptEvents;
        return $this;
    }

    /**
     * @see Scriptable::getScriptAction()
     */
    public function getScriptAction()
    {
        return $this->scriptAction;
    }

    /**
     * @see Scriptable::setScriptAction()
     */
    public function setScriptAction($scriptAction, array $scriptActionParameters = null)
    {
        $this->scriptAction = (string)$scriptAction;
        $this->setScriptActionParameters($scriptActionParameters);
        return $this;
    }

    /**
     * @see Scriptable::getScriptActionParameters()
     */
    public function getScriptActionParameters()
    {
        return $this->scriptActionParameters;
    }

    /**
     * @see Scriptable::setScriptActionParameters()
     */
    public function setScriptActionParameters(array $scriptActionParameters = null)
    {
        $this->scriptActionParameters = $scriptActionParameters;
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "frame3d";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->style3d) {
            $this->style3d->checkId();
            $domElement->setAttribute("style3d", $this->style3d->getId());
        } else if ($this->style3dId) {
            $domElement->setAttribute("style3d", $this->style3dId);
        }
        if ($this->scriptEvents) {
            $domElement->setAttribute("scriptevents", 1);
        }
        if ($this->scriptAction) {
            $scriptAction = array($this->scriptAction);
            if ($this->scriptActionParameters) {
                $scriptAction = array_merge($scriptAction, $this->scriptActionParameters);
            }
            $domElement->setAttribute("scriptaction", implode("'", $scriptAction));
        }
        return $domElement;
    }

}
