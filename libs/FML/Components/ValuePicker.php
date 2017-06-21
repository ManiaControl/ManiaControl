<?php

namespace FML\Components;

use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Script\Features\ScriptFeature;
use FML\Script\Features\ValuePickerFeature;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * ValuePicker Component
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ValuePicker implements Renderable, ScriptFeatureable
{

    /**
     * @var string $name ValuePicker name
     */
    protected $name = null;

    /**
     * @var ValuePickerFeature $feature ValuePicker Feature
     */
    protected $feature = null;

    /**
     * Create a new ValuePicker
     *
     * @api
     * @param string   $name    (optional) ValuePicker name
     * @param string[] $values  (optional) Possible values
     * @param string   $default (optional) Default value
     * @param Label    $label   (optional) ValuePicker label
     */
    public function __construct($name = null, array $values = null, $default = null, Label $label = null)
    {
        $this->feature = new ValuePickerFeature();
        if ($name) {
            $this->setName($name);
        }
        if ($values) {
            $this->setValues($values);
        }
        if ($default !== null) {
            $this->setDefault($default);
        }
        if ($label) {
            $this->setLabel($label);
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
     * @param string $name ValuePicker name
     * @return static
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }

    /**
     * Get the possible values
     *
     * @api
     * @return string[]
     */
    public function getValues()
    {
        return $this->feature->getValues();
    }

    /**
     * Set the possible values
     *
     * @api
     * @param array $values Possible values
     * @return static
     */
    public function setValues(array $values)
    {
        $this->feature->setValues($values);
        return $this;
    }

    /**
     * Get the default value
     *
     * @api
     * @return string
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
     * Get the Label
     *
     * @api
     * @return Label
     */
    public function getLabel()
    {
        $label = $this->feature->getLabel();
        if ($label) {
            return $label;
        }
        return $this->createLabel();
    }

    /**
     * Set the Label
     *
     * @api
     * @param Label $label ValuePicker Label
     * @return static
     */
    public function setLabel(Label $label)
    {
        $this->feature->setLabel($label);
        return $this;
    }

    /**
     * Create the Label
     *
     * @return Label
     */
    protected function createLabel()
    {
        $label = new Label();
        $this->setLabel($label);
        return $label;
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
        $entry->setVisible(false)
              ->setName($this->name);
        $this->setEntry($entry);
        return $entry;
    }

    /**
     * @see ScriptFeatureable::getScriptFeatures()
     */
    public function getScriptFeatures()
    {
        return ScriptFeature::collect($this->feature, $this->getLabel(), $this->feature->getEntry());
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $frame = new Frame();

        $label = $this->getLabel();
        $frame->addChild($label);

        $entry = $this->getEntry();
        $frame->addChild($entry);

        return $frame->render($domDocument);
    }

}
