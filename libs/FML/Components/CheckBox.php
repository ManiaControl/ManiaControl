<?php

namespace FML\Components;

use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Script\Features\CheckBoxFeature;
use FML\Script\Features\ScriptFeature;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * CheckBox Component
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBox implements Renderable, ScriptFeatureable
{

    /**
     * @var string $name CheckBox name
     */
    protected $name = null;

    /**
     * @var CheckBoxFeature $feature CheckBox Feature
     */
    protected $feature = null;

    /**
     * Construct a new CheckBox
     *
     * @api
     * @param string $name    (optional) CheckBox name
     * @param bool   $default (optional) Default value
     * @param Quad   $quad    (optional) CheckBox quad
     */
    public function __construct($name = null, $default = null, Quad $quad = null)
    {
        $this->feature = new CheckBoxFeature();
        if ($name) {
            $this->setName($name);
        }
        if ($default !== null) {
            $this->setDefault($default);
        }
        if ($quad) {
            $this->setQuad($quad);
        }
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
     * @param string $name CheckBox name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        $this->getEntry()
             ->setName($this->name);
        return $this;
    }

    /**
     * Get the default value
     *
     * @api
     * @return bool
     */
    public function getDefault()
    {
        return $this->feature->getDefault();
    }

    /**
     * Set the default value
     *
     * @api
     * @param bool $default Default value
     * @return static
     */
    public function setDefault($default)
    {
        $this->feature->setDefault($default);
        return $this;
    }

    /**
     * Get the enabled design
     *
     * @api
     * @return CheckBoxDesign
     */
    public function getEnabledDesign()
    {
        return $this->feature->getEnabledDesign();
    }

    /**
     * Set the enabled design
     *
     * @api
     * @param string|CheckBoxDesign $style    Style name, image url or checkbox design
     * @param string                $subStyle SubStyle name
     * @return static
     */
    public function setEnabledDesign($style, $subStyle = null)
    {
        if ($style instanceof CheckBoxDesign) {
            $this->feature->setEnabledDesign($style);
        } else {
            $checkBoxDesign = new CheckBoxDesign($style, $subStyle);
            $this->feature->setEnabledDesign($checkBoxDesign);
        }
        return $this;
    }

    /**
     * Get the disabled design
     *
     * @api
     * @return CheckBoxDesign
     */
    public function getDisabledDesign()
    {
        return $this->feature->getDisabledDesign();
    }

    /**
     * Set the disabled design
     *
     * @api
     * @param string|CheckBoxDesign $style    Style name, image url or checkbox design
     * @param string                $subStyle SubStyle name
     * @return static
     */
    public function setDisabledDesign($style, $subStyle = null)
    {
        if ($style instanceof CheckBoxDesign) {
            $this->feature->setDisabledDesign($style);
        } else {
            $checkBoxDesign = new CheckBoxDesign($style, $subStyle);
            $this->feature->setDisabledDesign($checkBoxDesign);
        }
        return $this;
    }

    /**
     * Get the CheckBox Quad
     *
     * @api
     * @return Quad
     */
    public function getQuad()
    {
        $quad = $this->feature->getQuad();
        if ($quad) {
            return $quad;
        }
        return $this->createQuad();
    }

    /**
     * Set the CheckBox Quad
     *
     * @api
     * @param Quad $quad CheckBox Quad
     * @return static
     */
    public function setQuad(Quad $quad)
    {
        $this->feature->setQuad($quad);
        return $this;
    }

    /**
     * Create the CheckBox Quad
     *
     * @return Quad
     */
    protected function createQuad()
    {
        $quad = new Quad();
        $quad->setSize(10, 10);
        $this->setQuad($quad);
        return $quad;
    }

    /**
     * Get the hidden Entry
     *
     * @return Entry
     */
    public function getEntry()
    {
        $entry = $this->feature->getEntry();
        if ($entry) {
            return $entry;
        }
        return $this->createEntry();
    }

    /**
     * Set the hidden Entry
     *
     * @param Entry $entry Hidden Entry
     * @return static
     * @deprecated
     */
    public function setEntry(Entry $entry)
    {
        $this->feature->setEntry($entry);
        return $this;
    }

    /**
     * Create the hidden Entry
     *
     * @return Entry
     */
    protected function createEntry()
    {
        $entry = new Entry();
        $entry->setVisible(false);
        if ($this->name) {
            $entry->setName($this->name);
        }
        $this->feature->setEntry($entry);
        return $entry;
    }

    /**
     * @see ScriptFeatureable::getScriptFeatures()
     */
    public function getScriptFeatures()
    {
        return ScriptFeature::collect($this->feature, $this->getQuad(), $this->getEntry());
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $frame = new Frame();

        $quad = $this->getQuad();
        $frame->addChild($quad);

        $entry = $this->getEntry();
        $frame->addChild($entry);

        return $frame->render($domDocument);
    }

}
