<?php

namespace FML;

use FML\Elements\Dico;
use FML\Script\Script;
use FML\Stylesheet\Stylesheet;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * Class representing a ManiaLink
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaLink
{

    /*
     * Constants
     */
    const VERSION_0           = 0;
    const VERSION_1           = 1;
    const VERSION_2           = 2;
    const VERSION_3           = 3;
    const BACKGROUND_0        = "0";
    const BACKGROUND_1        = "1";
    const BACKGROUND_STARS    = "stars";
    const BACKGROUND_STATIONS = "stations";
    const BACKGROUND_TITLE    = "title";

    /**
     * @var string $maniaLinkId ManiaLink ID
     */
    protected $maniaLinkId = null;

    /**
     * @var int $defaultVersion Default ManiaLink version
     */
    static protected $defaultVersion = self::VERSION_1;

    /**
     * @var int $version ManiaLink version
     */
    protected $version = self::VERSION_1;

    /**
     * @var string $name ManiaLink name
     */
    protected $name = null;

    /**
     * @var string $background Background
     */
    protected $background = null;

    /**
     * @var bool $navigable3d 3d navigable
     */
    protected $navigable3d = true;

    /**
     * @var int $timeout Timeout
     */
    protected $timeout = null;

    /**
     * @var Renderable[] $children Children
     */
    protected $children = array();

    /**
     * @var Dico $dico Dictionary
     */
    protected $dico = null;

    /**
     * @var Stylesheet $stylesheet Style sheet
     */
    protected $stylesheet = null;

    /**
     * @var Script $script Script
     */
    protected $script = null;

    /**
     * Create a new ManiaLink
     *
     * @api
     * @param string       $maniaLinkId (optional) ManiaLink ID
     * @param int          $version     (optional) Version
     * @param string       $name        (optional) Name
     * @param Renderable[] $children    (optional) Children
     * @return static
     */
    public static function create($maniaLinkId = null, $version = null, $name = null, array $children = null)
    {
        return new static($maniaLinkId, $version, $name, $children);
    }

    /**
     * Construct a new ManiaLink
     *
     * @api
     * @param string       $maniaLinkId (optional) ManiaLink ID
     * @param int          $version     (optional) Version
     * @param string       $name        (optional) Name
     * @param Renderable[] $children    (optional) Children
     */
    public function __construct($maniaLinkId = null, $version = null, $name = null, array $children = null)
    {
        if (is_string($version) && (!$name || is_array($name)) && !$children) {
            // backwards-compatibility (version has been introduced later, if it's a string it's supposed to be the name)
            $children = $name;
            $name     = $version;
            $version  = null;
        }
        if ($maniaLinkId) {
            $this->setId($maniaLinkId);
        }
        if ($version === null) {
            $this->setVersion(static::$defaultVersion);
        } else {
            $this->setVersion($version);
        }
        if ($name) {
            $this->setName($name);
        }
        if ($children) {
            $this->setChildren($children);
        }
    }

    /**
     * Get the ID
     *
     * @api
     * @return string
     */
    public function getId()
    {
        return $this->maniaLinkId;
    }

    /**
     * Set the ID
     *
     * @api
     * @param string $maniaLinkId ManiaLink ID
     * @return static
     */
    public function setId($maniaLinkId)
    {
        $this->maniaLinkId = (string)$maniaLinkId;
        if ($this->maniaLinkId && !$this->name) {
            $this->setName($this->maniaLinkId);
        }
        return $this;
    }

    /**
     * Get the default version
     *
     * @api
     * @return int
     */
    public static function getDefaultVersion()
    {
        return static::$defaultVersion;
    }

    /**
     * Get the version
     *
     * @api
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the default version
     *
     * @api
     * @param int $defaultVersion Default ManiaLink version
     */
    public static function setDefaultVersion($defaultVersion)
    {
        static::$defaultVersion = (int)$defaultVersion;
    }

    /**
     * Set the version
     *
     * @api
     * @param int $version ManiaLink version
     * @return static
     */
    public function setVersion($version)
    {
        $this->version = (int)$version;
        return $this;
    }

    /**
     * Get the name
     *
     * @api
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @api
     * @param string $name ManiaLink Name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the background
     *
     * @api
     * @return string
     */
    public function getBackground()
    {
        return $this->background;
    }

    /**
     * Set the background
     *
     * @api
     * @param string $background Background value
     * @return static
     */
    public function setBackground($background)
    {
        $this->background = (string)$background;
        return $this;
    }

    /**
     * Get navigable3d
     *
     * @api
     * @return bool
     */
    public function getNavigable3d()
    {
        return $this->navigable3d;
    }

    /**
     * Set navigable3d
     *
     * @api
     * @param bool $navigable3d If the ManiaLink should be 3d navigable
     * @return static
     */
    public function setNavigable3d($navigable3d)
    {
        $this->navigable3d = (bool)$navigable3d;
        return $this;
    }

    /**
     * Get the timeout
     *
     * @api
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Set the timeout
     *
     * @api
     * @param int $timeout Timeout duration
     * @return static
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int)$timeout;
        return $this;
    }

    /**
     * Get children
     *
     * @api
     * @return Renderable[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add a child
     *
     * @api
     * @param Renderable $child Child Element to add
     * @return static
     * @deprecated Use addChild()
     * @see        ManiaLink::addChild()
     */
    public function add(Renderable $child)
    {
        return $this->addChild($child);
    }

    /**
     * Add a child
     *
     * @api
     * @param Renderable $child Child Element to add
     * @return static
     */
    public function addChild(Renderable $child)
    {
        if (!in_array($child, $this->children, true)) {
            array_push($this->children, $child);
        }
        return $this;
    }

    /**
     * Add children
     *
     * @api
     * @param Renderable[] $children Child Elements to add
     * @return static
     */
    public function addChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
        return $this;
    }

    /**
     * Set children
     *
     * @api
     * @param Renderable[] $children Child Elements
     * @return static
     */
    public function setChildren(array $children)
    {
        return $this->removeAllChildren()
                    ->addChildren($children);
    }

    /**
     * Remove all children
     *
     * @api
     * @return static
     * @deprecated Use removeAllChildren()
     * @see        ManiaLink::removeAllChildren()
     */
    public function removeChildren()
    {
        return $this->removeAllChildren();
    }

    /**
     * Remove all children
     *
     * @api
     * @return static
     */
    public function removeAllChildren()
    {
        $this->children = array();
        return $this;
    }

    /**
     * Get the Dictionary
     *
     * @api
     * @param bool $createIfEmpty (optional) If the Dico should be created if it doesn't exist yet
     * @return Dico
     */
    public function getDico($createIfEmpty = true)
    {
        if (!$this->dico && $createIfEmpty) {
            $this->setDico(new Dico());
        }
        return $this->dico;
    }

    /**
     * Set the Dictionary
     *
     * @api
     * @param Dico $dico Dictionary
     * @return static
     */
    public function setDico(Dico $dico = null)
    {
        $this->dico = $dico;
        return $this;
    }

    /**
     * Get the Stylesheet
     *
     * @api
     * @return Stylesheet
     */
    public function getStylesheet($createIfEmpty = true)
    {
        if (!$this->stylesheet && $createIfEmpty) {
            return $this->createStylesheet();
        }
        return $this->stylesheet;
    }

    /**
     * Set the Stylesheet
     *
     * @api
     * @param Stylesheet $stylesheet Stylesheet
     * @return static
     */
    public function setStylesheet(Stylesheet $stylesheet = null)
    {
        $this->stylesheet = $stylesheet;
        return $this;
    }

    /**
     * Create and assign a new Stylesheet if necessary
     *
     * @api
     * @return Stylesheet
     */
    public function createStylesheet()
    {
        if ($this->stylesheet) {
            return $this->stylesheet;
        }
        $stylesheet = new Stylesheet();
        $this->setStylesheet($stylesheet);
        return $this->stylesheet;
    }

    /**
     * Get the Script
     *
     * @api
     * @param bool $createIfEmpty (optional) Create the script if it's not set yet
     * @return Script
     */
    public function getScript($createIfEmpty = true)
    {
        if (!$this->script && $createIfEmpty) {
            return $this->createScript();
        }
        return $this->script;
    }

    /**
     * Set the Script
     *
     * @api
     * @param Script $script Script
     * @return static
     */
    public function setScript(Script $script = null)
    {
        $this->script = $script;
        return $this;
    }

    /**
     * Create and assign a new Script if necessary
     *
     * @api
     * @return Script
     */
    public function createScript()
    {
        if ($this->script) {
            return $this->script;
        }
        $script = new Script();
        $this->setScript($script);
        return $this->script;
    }

    /**
     * Render the ManiaLink
     *
     * @param bool         $echo        (optional) If the XML text should be echoed and the Content-Type header should be set
     * @param \DOMDocument $domDocument (optional) DOMDocument for which the ManiaLink should be rendered
     * @return \DOMDocument
     */
    public function render($echo = false, $domDocument = null)
    {
        $isChild = (bool)$domDocument;
        if (!$isChild) {
            $domDocument                = new \DOMDocument("1.0", "utf-8");
            $domDocument->xmlStandalone = true;
        }
        $maniaLink = $domDocument->createElement("manialink");
        if (!$isChild) {
            $domDocument->appendChild($maniaLink);
        }

        if ($this->maniaLinkId) {
            $maniaLink->setAttribute("id", $this->maniaLinkId);
        }
        if ($this->version > 0) {
            $maniaLink->setAttribute("version", $this->version);
        }
        if ($this->name) {
            $maniaLink->setAttribute("name", $this->name);
        }
        if ($this->background) {
            $maniaLink->setAttribute("background", $this->background);
        }
        if (!$this->navigable3d) {
            $maniaLink->setAttribute("navigable3d", "0");
        }
        if ($this->timeout) {
            $timeoutXml = $domDocument->createElement("timeout", $this->timeout);
            $maniaLink->appendChild($timeoutXml);
        }
        if ($this->dico) {
            $dicoXml = $this->dico->render($domDocument);
            $maniaLink->appendChild($dicoXml);
        }

        $scriptFeatures = array();
        foreach ($this->children as $child) {
            $childXml = $child->render($domDocument);
            $maniaLink->appendChild($childXml);
            if ($child instanceof ScriptFeatureable) {
                $scriptFeatures = array_merge($scriptFeatures, $child->getScriptFeatures());
            }
        }

        if ($this->stylesheet) {
            $stylesheetXml = $this->stylesheet->render($domDocument);
            $maniaLink->appendChild($stylesheetXml);
        }

        if ($scriptFeatures) {
            $this->createScript()
                 ->loadFeatures($scriptFeatures);
        }
        if ($this->script) {
            if ($this->script->needsRendering()) {
                $scriptXml = $this->script->render($domDocument);
                $maniaLink->appendChild($scriptXml);
            }
            $this->script->resetGenericScriptLabels();
        }

        if ($isChild) {
            return $maniaLink;
        }
        if ($echo) {
            header("Content-Type: application/xml; charset=utf-8;");
            echo $domDocument->saveXML();
        }
        return $domDocument;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render()
                    ->saveXML();
    }

}
