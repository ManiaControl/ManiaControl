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
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ValuePicker implements Renderable, ScriptFeatureable {
	/*
	 * Protected Properties
	 */
	protected $name = null;
	protected $feature = null;

	/**
	 * Create a new ValuePicker Component
	 *
	 * @param string $name    (optional) CheckBox Name
	 * @param array  $values  (optional) Possible Values
	 * @param bool   $default (optional) Default Value
	 * @param Label  $label   (optional) ValuePicker Label
	 */
	public function __construct($name = null, array $values = array(), $default = null, Label $label = null) {
		$this->feature = new ValuePickerFeature();
		$this->setName($name);
		$this->setValues($values);
		$this->setDefault($default);
		$this->setLabel($label);
	}

	/**
	 * Set the Name
	 *
	 * @param string $name ValuePicker Name
	 * @return \FML\Components\ValuePicker
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the possible Values
	 *
	 * @param array $values Possible Values
	 * @return \FML\Components\ValuePicker
	 */
	public function setValues(array $values) {
		$this->feature->setValues($values);
		return $this;
	}

	/**
	 * Set the Default Value
	 *
	 * @param bool $default Default Value
	 * @return \FML\Components\ValuePicker
	 */
	public function setDefault($default) {
		$this->feature->setDefault($default);
		return $this;
	}

	/**
	 * Set the ValuePicker Label
	 *
	 * @param Label $label ValuePicker Label
	 * @return \FML\Components\ValuePicker
	 */
	public function setLabel(Label $label = null) {
		$this->feature->setLabel($label);
		return $this;
	}

	/**
	 * Get the ValuePicker Label
	 *
	 * @param bool $createIfEmpty (optional) Create the Label if it's not set
	 * @return \FML\Controls\Label
	 */
	public function getLabel($createIfEmpty = true) {
		if (!$this->feature->getLabel() && $createIfEmpty) {
			$label = new Label();
			$this->setLabel($label);
		}
		return $this->feature->getLabel();
	}

	/**
	 * @see \FML\Types\ScriptFeatureable::getScriptFeatures()
	 */
	public function getScriptFeatures() {
		return ScriptFeature::collect($this->feature, $this->getLabel(), $this->feature->getEntry());
	}

	/**
	 * @see \ManiaControl\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$frame = new Frame();

		$label = $this->getLabel();
		$frame->add($label);

		$entry = $this->buildEntry();
		$frame->add($entry);
		$this->feature->setEntry($entry);

		return $frame->render($domDocument);
	}

	/**
	 * Build the hidden Entry
	 *
	 * @return Entry
	 */
	protected function buildEntry() {
		$entry = new Entry();
		$entry->setVisible(false);
		$entry->setName($this->name);
		return $entry;
	}
}
