<?php

namespace FML\Controls;

use FML\Script\Features\ActionTrigger;
use FML\Script\Features\ControlScript;
use FML\Script\Features\MapInfo;
use FML\Script\Features\PlayerProfile;
use FML\Script\Features\ScriptFeature;
use FML\Script\Features\Toggle;
use FML\Script\Features\Tooltip;
use FML\Script\Features\UISound;
use FML\Script\ScriptLabel;
use FML\Types\Identifiable;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;
use FML\UniqueID;

/**
 * Base Control
 * (CMlControl)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Control implements Identifiable, Renderable, ScriptFeatureable
{

    /*
     * Constants
     */
    const CENTER  = 'center';
    const CENTER2 = 'center2';
    const TOP     = 'top';
    const RIGHT   = 'right';
    const BOTTOM  = 'bottom';
    const LEFT    = 'left';

    /**
     * @var string $controlId Control Id
     */
    protected $controlId = null;

    /**
     * @var float $posX X position
     */
    protected $posX = 0.;

    /**
     * @var float $posY Y position
     */
    protected $posY = 0.;

    /**
     * @var float $posZ Z position
     */
    protected $posZ = 0.;

    /**
     * @var float $width Width
     */
    protected $width = 0.;

    /**
     * @var float $height Height
     */
    protected $height = 0.;

    /**
     * @var string $defaultHorizontalAlign Default horizontal alignment
     */
    static protected $defaultHorizontalAlign = self::CENTER;

    /**
     * @var string $horizontalAlign Horizontal alignment
     */
    protected $horizontalAlign = self::CENTER;

    /**
     * @var string $defaultVerticalAlign Default vertical alignment
     */
    static protected $defaultVerticalAlign = self::CENTER2;

    /**
     * @var string $verticalAlign Vertical alignment
     */
    protected $verticalAlign = self::CENTER2;

    /**
     * @var float $scale Scale
     */
    protected $scale = 1.;

    /**
     * @var bool $visible Visibility
     */
    protected $visible = true;

    /**
     * @var float $rotation Rotation
     */
    protected $rotation = 0.;

    /**
     * @var string[] $classes Style classes
     */
    protected $classes = array();

    /**
     * @var mixed[] $dataAttributes Data attributes
     */
    protected $dataAttributes = array();

    /**
     * @var ScriptFeature[] $scriptFeatures Script Features
     */
    protected $scriptFeatures = array();

    /**
     * Create a new Control
     *
     * @api
     * @param string $controlId (optional) Control Id
     * @return static
     */
    public static function create($controlId = null)
    {
        return new static($controlId);
    }

    /**
     * Construct a new Control
     *
     * @api
     * @param string $controlId (optional) Control Id
     */
    public function __construct($controlId = null)
    {
        if ($controlId) {
            $this->setId($controlId);
        }
        $this->setHorizontalAlign(static::$defaultHorizontalAlign);
        $this->setVerticalAlign(static::$defaultVerticalAlign);
    }

    /**
     * @see Identifiable::getId()
     */
    public function getId()
    {
        return $this->controlId;
    }

    /**
     * @see Identifiable::setId()
     */
    public function setId($controlId)
    {
        $this->controlId = (string)$controlId;
        return $this;
    }

    /**
     * @see Identifiable::checkId()
     */
    public function checkId()
    {
        return UniqueID::check($this);
    }

    /**
     * Get the X position
     *
     * @api
     * @return float
     */
    public function getX()
    {
        return $this->posX;
    }

    /**
     * Set the X position
     *
     * @api
     * @param float $posX Horizontal position
     * @return static
     */
    public function setX($posX)
    {
        $this->posX = (float)$posX;
        return $this;
    }

    /**
     * Get the Y position
     *
     * @api
     * @return float
     */
    public function getY()
    {
        return $this->posY;
    }

    /**
     * Set the Y position
     *
     * @api
     * @param float $posY Vertical position
     * @return static
     */
    public function setY($posY)
    {
        $this->posY = (float)$posY;
        return $this;
    }

    /**
     * Get the Z position
     *
     * @api
     * @return float
     */
    public function getZ()
    {
        return $this->posZ;
    }

    /**
     * Set the Z position
     *
     * @api
     * @param float $posZ Depth
     * @return static
     */
    public function setZ($posZ)
    {
        $this->posZ = (float)$posZ;
        return $this;
    }

    /**
     * Set the Control position
     *
     * @api
     * @param float $posX Horizontal position
     * @param float $posY Vertical position
     * @param float $posZ (optional) Depth
     * @return static
     */
    public function setPosition($posX, $posY, $posZ = null)
    {
        $this->setX($posX)
             ->setY($posY);
        if ($posZ !== null) {
            $this->setZ($posZ);
        }
        return $this;
    }

    /**
     * Get the width
     *
     * @api
     * @return float
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set the width
     *
     * @api
     * @param float $width Control width
     * @return static
     */
    public function setWidth($width)
    {
        $this->width = (float)$width;
        return $this;
    }

    /**
     * Get the height
     *
     * @api
     * @return float
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set the height
     *
     * @api
     * @param float $height Control height
     * @return static
     */
    public function setHeight($height)
    {
        $this->height = (float)$height;
        return $this;
    }

    /**
     * Set the size
     *
     * @api
     * @param float $width  Control width
     * @param float $height Control height
     * @return static
     */
    public function setSize($width, $height)
    {
        return $this->setWidth($width)
                    ->setHeight($height);
    }

    /**
     * Get the default horizontal alignment
     *
     * @api
     * @return string
     */
    public static function getDefaultHorizontalAlign()
    {
        return static::$defaultHorizontalAlign;
    }

    /**
     * Get the horizontal alignment
     *
     * @api
     * @return string
     */
    public function getHorizontalAlign()
    {
        return $this->horizontalAlign;
    }

    /**
     * Set the default horizontal alignment
     *
     * @api
     * @param string $defaultHorizontalAlignment Default horizontal alignment
     */
    public static function setDefaultHorizontalAlign($defaultHorizontalAlignment)
    {
        static::$defaultHorizontalAlign = (string)$defaultHorizontalAlignment;
    }

    /**
     * Set the horizontal alignment
     *
     * @api
     * @param string $horizontalAlign Horizontal alignment
     * @return static
     * @deprecated Use setHorizontalAlign()
     * @see        Control::setHorizontalAlign()
     */
    public function setHAlign($horizontalAlign)
    {
        return $this->setHorizontalAlign($horizontalAlign);
    }

    /**
     * Set the horizontal alignment
     *
     * @api
     * @param string $horizontalAlign Horizontal alignment
     * @return static
     */
    public function setHorizontalAlign($horizontalAlign)
    {
        $this->horizontalAlign = (string)$horizontalAlign;
        return $this;
    }

    /**
     * Get the default vertical alignment
     *
     * @api
     * @return string
     */
    public static function getDefaultVerticalAlign()
    {
        return static::$defaultVerticalAlign;
    }

    /**
     * Get the vertical alignment
     *
     * @api
     * @return string
     */
    public function getVerticalAlign()
    {
        return $this->verticalAlign;
    }

    /**
     * Set the default vertical alignment
     *
     * @api
     * @param string $defaultVerticalAlignment Default vertical alignment
     */
    public static function setDefaultVerticalAlign($defaultVerticalAlignment)
    {
        static::$defaultVerticalAlign = (string)$defaultVerticalAlignment;
    }

    /**
     * Set the vertical alignment
     *
     * @api
     * @param string $verticalAlign Vertical alignment
     * @return static
     * @deprecated Use setVerticalAlign()
     * @see        Control::setVerticalAlign()
     */
    public function setVAlign($verticalAlign)
    {
        return $this->setVerticalAlign($verticalAlign);
    }

    /**
     * Set the vertical alignment
     *
     * @api
     * @param string $verticalAlign Vertical alignment
     * @return static
     */
    public function setVerticalAlign($verticalAlign)
    {
        $this->verticalAlign = (string)$verticalAlign;
        return $this;
    }

    /**
     * Set the horizontal and the vertical alignment
     *
     * @api
     * @param string $horizontalAlign Horizontal alignment
     * @param string $verticalAlign   Vertical alignment
     * @return static
     */
    public function setAlign($horizontalAlign, $verticalAlign)
    {
        return $this->setHorizontalAlign($horizontalAlign)
                    ->setVerticalAlign($verticalAlign);
    }

    /**
     * Center the default alignment
     *
     * @api
     */
    public static function centerDefaultAlign()
    {
        static::$defaultHorizontalAlign = static::CENTER;
        static::$defaultVerticalAlign   = static::CENTER2;
    }

    /**
     * Center the alignment
     *
     * @api
     * @return static
     */
    public function centerAlign()
    {
        return $this->setAlign(self::CENTER, self::CENTER2);
    }

    /**
     * Reset the alignment
     *
     * @api
     * @return static
     * @deprecated Use clearAlign()
     * @see        Control::clearAlign()
     */
    public function resetAlign()
    {
        return $this->clearAlign();
    }

    /**
     * Clear the default alignment
     *
     * @api
     */
    public static function clearDefaultAlign()
    {
        static::$defaultHorizontalAlign = null;
        static::$defaultVerticalAlign   = null;
    }

    /**
     * Clear the alignment
     *
     * @api
     * @return static
     */
    public function clearAlign()
    {
        $this->horizontalAlign = null;
        $this->verticalAlign   = null;
        return $this;
    }

    /**
     * Get the scale
     *
     * @api
     * @return float
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * Set the scale
     *
     * @api
     * @param float $scale Control scale
     * @return static
     */
    public function setScale($scale)
    {
        $this->scale = (float)$scale;
        return $this;
    }

    /**
     * Get the visibility
     *
     * @api
     * @return bool
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visibility
     *
     * @api
     * @param bool $visible If the Control should be visible
     * @return static
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Get the rotation
     *
     * @api
     * @return float
     */
    public function getRotation()
    {
        return $this->rotation;
    }

    /**
     * Set the rotation
     *
     * @api
     * @param float $rotation Control rotation
     * @return static
     */
    public function setRotation($rotation)
    {
        $this->rotation = (float)$rotation;
        return $this;
    }

    /**
     * Get style classes
     *
     * @api
     * @return string[]
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Add a new style class
     *
     * @api
     * @param string $class Style class
     * @return static
     */
    public function addClass($class)
    {
        $class = (string)$class;
        if (!in_array($class, $this->classes)) {
            array_push($this->classes, $class);
        }
        return $this;
    }

    /**
     * Add new style classes
     *
     * @api
     * @param string[] $classes Style classes
     * @return static
     */
    public function addClasses(array $classes)
    {
        foreach ($classes as $class) {
            $this->addClass($class);
        }
        return $this;
    }

    /**
     * Remove all style classes
     *
     * @api
     * @return static
     */
    public function removeAllClasses()
    {
        $this->classes = array();
        return $this;
    }

    /**
     * Check if a data attribute is set
     *
     * @api
     * @param string $name Name
     * @return bool
     */
    public function hasDataAttribute($name)
    {
        return isset($this->dataAttributes[$name]);
    }

    /**
     * Get data attribute
     *
     * @api
     * @param string $name Name
     * @return mixed
     */
    public function getDataAttribute($name)
    {
        if (isset($this->dataAttributes[$name])) {
            return $this->dataAttributes[$name];
        }
        return null;
    }

    /**
     * Get data attributes
     *
     * @api
     * @return mixed[]
     */
    public function getDataAttributes()
    {
        return $this->dataAttributes;
    }

    /**
     * Add data attribute
     *
     * @api
     * @param string $name  Name
     * @param mixed  $value Value
     * @return static
     */
    public function addDataAttribute($name, $value)
    {
        $this->dataAttributes[$name] = $value;
        return $this;
    }

    /**
     * Add multiple data attributes
     *
     * @api
     * @param mixed[] $dataAttributes Data attributes
     * @return static
     */
    public function addDataAttributes(array $dataAttributes)
    {
        foreach ($dataAttributes as $name => $value) {
            $this->addDataAttribute($name, $value);
        }
        return $this;
    }

    /**
     * Set data attributes (replacing all previous attributes)
     *
     * @api
     * @param mixed[] $dataAttributes Data attributes
     * @return static
     */
    public function setDataAttributes(array $dataAttributes)
    {
        return $this->removeAllDataAttributes()
                    ->addDataAttributes($dataAttributes);
    }

    /**
     * Remove data attribute
     *
     * @api
     * @param string $name Name
     * @return static
     */
    public function removeDataAttribute($name)
    {
        unset($this->dataAttributes[$name]);
        return $this;
    }

    /**
     * Remove all data attributes
     *
     * @api
     * @return static
     */
    public function removeAllDataAttributes()
    {
        $this->dataAttributes = array();
        return $this;
    }

    /**
     * @see ScriptFeatureable::getScriptFeatures()
     */
    public function getScriptFeatures()
    {
        return $this->scriptFeatures;
    }

    /**
     * Add a new Script Feature
     *
     * @api
     * @param ScriptFeature $scriptFeature Script Feature
     * @return static
     */
    public function addScriptFeature(ScriptFeature $scriptFeature)
    {
        if (!in_array($scriptFeature, $this->scriptFeatures, true)) {
            array_push($this->scriptFeatures, $scriptFeature);
        }
        return $this;
    }

    /**
     * Add new Script Features
     *
     * @api
     * @param ScriptFeature[] $scriptFeatures Script Features
     * @return static
     */
    public function addScriptFeatures(array $scriptFeatures)
    {
        foreach ($scriptFeatures as $scriptFeature) {
            $this->addScriptFeature($scriptFeature);
        }
        return $this;
    }

    /**
     * Remove all Script Features
     *
     * @api
     * @return static
     * @deprecated Use removeAllScriptFeatures()
     * @see        Control::removeAllScriptFeatures()
     */
    public function removeScriptFeatures()
    {
        return $this->removeAllScriptFeatures();
    }

    /**
     * Remove all Script Features
     *
     * @api
     * @return static
     */
    public function removeAllScriptFeatures()
    {
        $this->scriptFeatures = array();
        return $this;
    }

    /**
     * Add a dynamic Action Trigger
     *
     * @api
     * @param string $actionName Action to trigger
     * @param string $eventLabel (optional) Event on which the action is triggered
     * @return static
     */
    public function addActionTriggerFeature($actionName, $eventLabel = ScriptLabel::MOUSECLICK)
    {
        $actionTrigger = new ActionTrigger($actionName, $this, $eventLabel);
        $this->addScriptFeature($actionTrigger);
        return $this;
    }

    /**
     * Add a dynamic Feature opening the current map info
     *
     * @api
     * @param string $eventLabel (optional) Event on which the map info will be opened
     * @return static
     */
    public function addMapInfoFeature($eventLabel = ScriptLabel::MOUSECLICK)
    {
        $mapInfo = new MapInfo($this, $eventLabel);
        $this->addScriptFeature($mapInfo);
        return $this;
    }

    /**
     * Add a dynamic Feature to open a specific player profile
     *
     * @api
     * @param string $login      Login of the player
     * @param string $eventLabel (optional) Event on which the player profile will be opened
     * @return static
     */
    public function addPlayerProfileFeature($login, $eventLabel = ScriptLabel::MOUSECLICK)
    {
        $playerProfile = new PlayerProfile($login, $this, $eventLabel);
        $this->addScriptFeature($playerProfile);
        return $this;
    }

    /**
     * Add a dynamic Feature playing a UISound
     *
     * @api
     * @param string $soundName  UISound name
     * @param int    $variant    (optional) Sound variant
     * @param string $eventLabel (optional) Event on which the sound will be played
     * @return static
     */
    public function addUISoundFeature($soundName, $variant = 0, $eventLabel = ScriptLabel::MOUSECLICK)
    {
        $uiSound = new UISound($soundName, $this, $variant, $eventLabel);
        $this->addScriptFeature($uiSound);
        return $this;
    }

    /**
     * Add a dynamic Feature toggling another Control
     *
     * @api
     * @param Control $toggledControl Toggled Control
     * @param string  $labelName      (optional) Script label name
     * @param bool    $onlyShow       (optional) If it should only show the Control but not toggle
     * @param bool    $onlyHide       (optional) If it should only hide the Control but not toggle
     * @return static
     */
    public function addToggleFeature(Control $toggledControl, $labelName = ScriptLabel::MOUSECLICK, $onlyShow = false, $onlyHide = false)
    {
        $toggle = new Toggle($this, $toggledControl, $labelName, $onlyShow, $onlyHide);
        $this->addScriptFeature($toggle);
        return $this;
    }

    /**
     * Add a dynamic Feature showing a Tooltip on hovering
     *
     * @api
     * @param Control $tooltipControl Tooltip Control
     * @param bool    $stayOnClick    (optional) Whether the Tooltip should stay on click
     * @param bool    $invert         (optional) Whether the visibility toggling should be inverted
     * @return static
     */
    public function addTooltipFeature(Control $tooltipControl, $stayOnClick = false, $invert = false)
    {
        $tooltip = new Tooltip($this, $tooltipControl, $stayOnClick, $invert);
        $this->addScriptFeature($tooltip);
        return $this;
    }

    /**
     * Add a dynamic Feature showing a Tooltip on hovering
     *
     * @api
     * @param Label  $tooltipLabel Tooltip Label
     * @param string $text         Text to display on the Tooltip Label
     * @param bool   $stayOnClick  (optional) Whether the Tooltip should stay on click
     * @param bool   $invert       (optional) Whether the visibility toggling should be inverted
     * @return static
     */
    public function addTooltipLabelFeature(Label $tooltipLabel, $text, $stayOnClick = false, $invert = false)
    {
        $tooltip = new Tooltip($this, $tooltipLabel, $stayOnClick, $invert, $text);
        $this->addScriptFeature($tooltip);
        return $this;
    }

    /**
     * Add a custom Control Script text part
     *
     * @api
     * @param string $scriptText Script text
     * @param string $label      (optional) Script label name
     * @return static
     */
    public function addScriptText($scriptText, $label = ScriptLabel::MOUSECLICK)
    {
        $customText = new ControlScript($this, $scriptText, $label);
        $this->addScriptFeature($customText);
        return $this;
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement($this->getTagName());
        if ($this->controlId) {
            $domElement->setAttribute("id", $this->controlId);
        }
        if ($this->posX || $this->posY) {
            $domElement->setAttribute("pos", "{$this->posX} {$this->posY}");
        }
        if ($this->posX || $this->posY || $this->posZ) {
            // backwards-compatibility
            $domElement->setAttribute("posn", "{$this->posX} {$this->posY} {$this->posZ}");
        }
        if ($this->posZ) {
            $domElement->setAttribute("z-index", $this->posZ);
        }
        if ($this->width > 0. || $this->height > 0.) {
            $domElement->setAttribute("size", "{$this->width} {$this->height}");
            // backwards-compatibility
            $domElement->setAttribute("sizen", "{$this->width} {$this->height}");
            if ($this->width > 0.) {
                // backwards-compatibility
                $domElement->setAttribute("width", $this->width);
            }
            if ($this->height > 0.) {
                // backwards-compatibility
                $domElement->setAttribute("height", $this->height);
            }
        }
        if ($this->horizontalAlign) {
            $domElement->setAttribute("halign", $this->horizontalAlign);
        }
        if ($this->verticalAlign) {
            $domElement->setAttribute("valign", $this->verticalAlign);
        }
        if ($this->scale != 1.) {
            $domElement->setAttribute("scale", $this->scale);
        }
        if (!$this->visible) {
            $domElement->setAttribute("hidden", "1");
        }
        if ($this->rotation) {
            $domElement->setAttribute("rot", $this->rotation);
        }
        if ($this->classes) {
            $classes = implode(" ", $this->classes);
            $domElement->setAttribute("class", $classes);
        }
        foreach ($this->dataAttributes as $dataAttributeName => $dataAttributeValue) {
            $domElement->setAttribute("data-" . $dataAttributeName, $dataAttributeValue);
        }
        return $domElement;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        $domDocument = new \DOMDocument("1.0", "utf-8");
        $domDocument->appendChild($this->render($domDocument));
        return $domDocument->saveXML($domDocument->documentElement);
    }

    /**
     * Get the tag name of the Control
     *
     * @return string
     */
    abstract public function getTagName();

    /**
     * Get the ManiaScript class of the Control
     *
     * @return string
     */
    abstract public function getManiaScriptClass();

}
