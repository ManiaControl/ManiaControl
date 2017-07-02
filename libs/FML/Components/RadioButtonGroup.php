<?php

namespace FML\Components;

use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Script\Features\RadioButtonGroupFeature;
use FML\Script\Features\ScriptFeature;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * RadioButtonGroup Component
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RadioButtonGroup implements Renderable, ScriptFeatureable
{

    /**
     * @var string $name RadioButtonGroup name
     */
    protected $name = null;

    /**
     * @var RadioButtonGroupFeature $feature RadioButtonGroup Feature
     */
    protected $feature = null;

    /**
     * Construct a new RadioButtonGroup
     *
     * @api
     * @param string $name (optional) RadioButtonGroup name
     */
    public function __construct($name = null)
    {
        $this->feature = new RadioButtonGroupFeature();
        if ($name) {
            $this->setName($name);
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
     * @param string $name RadioButtonGroup name
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
     * Get RadioButtons
     *
     * @api
     * @return CheckBox[]
     */
    public function getRadioButtons()
    {
        return $this->feature->getRadioButtons();
    }

    /**
     * Set RadioButtons
     *
     * @api
     * @param CheckBox[] $radioButtons RadioButtons
     * @return static
     */
    public function setRadioButtons(array $radioButtons)
    {
        $this->removeAllRadioButtons()
             ->addRadioButtons($radioButtons);
        return $this;
    }

    /**
     * Add a new RadioButton to the group
     *
     * @api
     * @param CheckBox $radioButton RadioButton
     * @return static
     */
    public function addRadioButton(CheckBox $radioButton)
    {
        $this->feature->addRadioButton($radioButton);
        return $this;
    }

    /**
     * Add new RadioButtons to the group
     *
     * @api
     * @param CheckBox[] $radioButtons RadioButtons
     * @return static
     */
    public function addRadioButtons(array $radioButtons)
    {
        $this->feature->addRadioButtons($radioButtons);
        return $this;
    }

    /**
     * Remove all RadioButtons
     *
     * @api
     * @return static
     */
    public function removeAllRadioButtons()
    {
        $this->feature->removeAllRadioButtons();
        return $this;
    }

    /**
     * @see ScriptFeatureable::getScriptFeatures()
     */
    public function getScriptFeatures()
    {
        return ScriptFeature::collect($this->feature, $this->getEntry());
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $frame = new Frame();

        $entry = $this->getEntry();
        $frame->addChild($entry);

        return $frame->render($domDocument);
    }

}
