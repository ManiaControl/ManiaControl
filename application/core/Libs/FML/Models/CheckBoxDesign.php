<?php

namespace FML\Models;

use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Icons64x64_1;
use FML\Script\Builder;
use FML\Types\Styleable;
use FML\Types\SubStyleable;

/**
 * Class representing CheckBox Design
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBoxDesign implements Styleable, SubStyleable {
	/*
	 * Protected Properties
	 */
	protected $url = null;
	protected $style = null;
	protected $subStyle = null;

	/**
	 * Create the Default Enabled Design
	 *
	 * @return \FML\Models\CheckBoxDesign
	 */
	public static function defaultEnabledDesign() {
		$checkBoxDesign = new CheckBoxDesign(Quad_Icons64x64_1::STYLE, Quad_Icons64x64_1::SUBSTYLE_LvlGreen);
		return $checkBoxDesign;
	}

	/**
	 * Create the Default Disabled Design
	 *
	 * @return \FML\Models\CheckBoxDesign
	 */
	public static function defaultDisabledDesign() {
		$checkBoxDesign = new CheckBoxDesign(Quad_Icons64x64_1::STYLE, Quad_Icons64x64_1::SUBSTYLE_LvlRed);
		return $checkBoxDesign;
	}

	/**
	 * Create a new CheckBox Design
	 *
	 * @param string $style    Style Name or Image Url
	 * @param string $subStyle SubStyle Name
	 */
	public function __construct($style, $subStyle = null) {
		if ($subStyle === null) {
			$this->setImageUrl($style);
		} else {
			$this->setStyle($style);
			$this->setSubStyle($subStyle);
		}
	}

	/**
	 * Set the Image Url
	 *
	 * @param string $url Image Url
	 * @return \FML\Models\CheckBoxDesign
	 */
	public function setImageUrl($url) {
		$this->url      = (string)$url;
		$this->style    = null;
		$this->subStyle = null;
		return $this;
	}

	/**
	 * @see \FML\Types\Styleable::setStyle()
	 */
	public function setStyle($style) {
		$this->style = (string)$style;
		$this->url   = null;
		return $this;
	}

	/**
	 * @see \FML\Types\SubStyleable::setSubStyle()
	 */
	public function setSubStyle($subStyle) {
		$this->subStyle = (string)$subStyle;
		$this->url      = null;
		return $this;
	}

	/**
	 * @see \FML\Types\SubStyleable::setStyles()
	 */
	public function setStyles($style, $subStyle) {
		$this->setStyle($style);
		$this->setSubStyle($subStyle);
		return $this;
	}

	/**
	 * Apply the Design to the given Quad
	 *
	 * @param Quad $quad CheckBox Quad
	 * @return \FML\Models\CheckBoxDesign
	 */
	public function applyToQuad(Quad $quad) {
		$quad->setImage($this->url);
		$quad->setStyles($this->style, $this->subStyle);
		return $this;
	}

	/**
	 * Get the CheckBox Design String
	 *
	 * @param bool $escaped (optional) Whether the String should be escaped for the Script
	 * @return string
	 */
	public function getDesignString($escaped = true) {
		if ($this->url !== null) {
			$string = $this->url;
		} else {
			$string = $this->style . '|' . $this->subStyle;;
		}
		if ($escaped) {
			return Builder::escapeText($string);
		}
		return $string;
	}
}
