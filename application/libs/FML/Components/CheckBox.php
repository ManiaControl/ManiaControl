<?php

namespace FML\Components;

use FML\Controls\Entry;
use FML\Controls\Frame;
use FML\Controls\Quad;
use FML\Models\CheckBoxDesign;
use FML\Script\Features\CheckBoxFeature;
use FML\Script\Features\ScriptFeature;
use FML\Types\Renderable;
use FML\Types\ScriptFeatureable;

/**
 * CheckBox Component
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBox implements Renderable, ScriptFeatureable {
	/*
	 * Protected properties
	 */
	protected $name = null;
	protected $feature = null;

	/**
	 * Create a new CheckBox Component
	 *
	 * @param string $name    (optional) CheckBox name
	 * @param bool   $default (optional) Default value
	 * @param Quad   $quad    (optional) CheckBox quad
	 */
	public function __construct($name = null, $default = null, Quad $quad = null) {
		$this->feature = new CheckBoxFeature();
		$this->setName($name);
		$this->setDefault($default);
		$this->setQuad($quad);
	}

	/**
	 * Set the name
	 *
	 * @param string $name CheckBox name
	 * @return static
	 */
	public function setName($name) {
		$this->name = (string)$name;
		return $this;
	}

	/**
	 * Set the default value
	 *
	 * @param bool $default Default value
	 * @return static
	 */
	public function setDefault($default) {
		$this->feature->setDefault($default);
		return $this;
	}

	/**
	 * Set the enabled Design
	 *
	 * @param string $style    Style name or image url
	 * @param string $subStyle SubStyle name
	 * @return static
	 */
	public function setEnabledDesign($style, $subStyle = null) {
		if ($style instanceof CheckBoxDesign) {
			$this->feature->setEnabledDesign($style);
		} else {
			$checkBoxDesign = new CheckBoxDesign($style, $subStyle);
			$this->feature->setEnabledDesign($checkBoxDesign);
		}
		return $this;
	}

	/**
	 * Set the disabled Design
	 *
	 * @param string $style    Style name or image url
	 * @param string $subStyle SubStyle name
	 * @return static
	 */
	public function setDisabledDesign($style, $subStyle = null) {
		if ($style instanceof CheckBoxDesign) {
			$this->feature->setDisabledDesign($style);
		} else {
			$checkBoxDesign = new CheckBoxDesign($style, $subStyle);
			$this->feature->setDisabledDesign($checkBoxDesign);
		}
		return $this;
	}

	/**
	 * Set the CheckBox Quad
	 *
	 * @param Quad $quad CheckBox Quad
	 * @return static
	 */
	public function setQuad(Quad $quad = null) {
		$this->feature->setQuad($quad);
		return $this;
	}

	/**
	 * @see \FML\Types\ScriptFeatureable::getScriptFeatures()
	 */
	public function getScriptFeatures() {
		return ScriptFeature::collect($this->feature, $this->getQuad(), $this->feature->getEntry());
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
			$this->setQuad($quad);
		}
		return $this->feature->getQuad();
	}

	/**
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$frame = new Frame();

		$quad = $this->getQuad();
		$frame->add($quad);

		$entry = $this->buildEntry();
		$frame->add($entry);
		$this->feature->setEntry($entry);

		return $frame->render($domDocument);
	}

	/**
	 * Build the hidden Entry
	 *
	 * @return \FML\Controls\Entry
	 */
	protected function buildEntry() {
		$entry = new Entry();
		$entry->setVisible(false)->setName($this->name);
		return $entry;
	}
}
