<?php

namespace FML\Components;

use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Models\CheckBoxDesign;
use FML\Script\Features\CheckBoxFeature;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * CheckBox Component
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBox implements Renderable, ScriptFeatureable {
	/*
	 * Protected Properties
	 */
	protected $name = null;
	protected $default = null;
	protected $hiddenEntryDisabled = null;
	protected $feature = null;

	/**
	 * Create a new CheckBox Component
	 *
	 * @param string $name    (optional) CheckBox Name
	 * @param bool   $default (optional) Default Value
	 * @param Quad   $quad    (optional) CheckBox Quad
	 */
	public function __construct($name = null, $default = null, Quad $quad = null) {
		$this->setName($name);
		$this->setDefault($default);
		$this->feature = new CheckBoxFeature();
		$this->feature->setQuad($quad);
	}

	/**
	 * Set the Name
	 *
	 * @param string $name CheckBox Name
	 * @return \FML\Components\CheckBox
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the Default Value
	 *
	 * @param bool $default Default Value
	 * @return \FML\Components\CheckBox
	 */
	public function setDefault($default) {
		$this->default = ($default ? 1 : 0);
		return $this;
	}

	/**
	 * Disable the hidden Entry that's sending the Value on Page Actions
	 *
	 * @param bool $disable (optional) Whether to disable or not
	 * @return \FML\Components\CheckBox
	 */
	public function disableHiddenEntry($disable = true) {
		$this->hiddenEntryDisabled = (bool)$disable;
		return $this;
	}

	/**
	 * Set the Enabled Design
	 *
	 * @param string $style    Style Name or Image Url
	 * @param string $subStyle SubStyle Name
	 * @return \FML\Components\CheckBox
	 */
	public function setEnabledDesign($style, $subStyle = null) {
		$checkBoxDesign = new CheckBoxDesign($style, $subStyle);
		$this->feature->setEnabledDesign($checkBoxDesign);
		return $this;
	}

	/**
	 * Set the Disabled Design
	 *
	 * @param string $style    Style Name or Image Url
	 * @param string $subStyle SubStyle Name
	 * @return \FML\Components\CheckBox
	 */
	public function setDisabledDesign($style, $subStyle = null) {
		$checkBoxDesign = new CheckBoxDesign($style, $subStyle);
		$this->feature->setDisabledDesign($checkBoxDesign);
		return $this;
	}

	/**
	 * Get the CheckBox Quad
	 *
	 * @param bool $createIfEmpty (optional) Create the Quad if it's not set
	 * @return \FML\Controls\Quad
	 */
	public function getQuad($createIfEmpty = true) {
		if (!$this->feature->getQuad() && $createIfEmpty) {
			$quad = new Quad();
			$quad->setSize(10, 10);
			$quad->setScriptEvents(true);
			$this->feature->setQuad($quad);
		}
		return $this->feature->getQuad();
	}

	/**
	 * Set the CheckBox Quad
	 *
	 * @param Quad $quad CheckBox Quad
	 * @return \FML\Components\CheckBox
	 */
	public function setQuad(Quad $quad) {
		$this->feature->setQuad($quad);
		return $this;
	}

	/**
	 * @see \FML\Types\ScriptFeatureable::getScriptFeatures()
	 */
	public function getScriptFeatures() {
		return array($this->feature);
	}

	/**
	 * @see \ManiaControl\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$frame = new Frame();
		$frame->addScriptFeature($this->feature);

		$quad = $this->getQuad();
		$frame->add($quad);

		if (!$this->hiddenEntryDisabled) {
			$entry = $this->buildEntry();
			$frame->add($entry);
			$this->feature->setEntry($entry);
		} else {
			$this->feature->setEntry(null);
		}

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
		$entry->setDefault($this->default);
		return $entry;
	}
}
