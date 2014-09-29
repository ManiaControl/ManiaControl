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
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class CheckBoxDesign implements Styleable, SubStyleable {
	/*
	 * Protected properties
	 */
	protected $url = null;
	protected $style = null;
	protected $subStyle = null;

	/**
	 * Create the default enabled Design
	 *
	 * @return static
	 */
	public static function defaultEnabledDesign() {
		return new static(Quad_Icons64x64_1::STYLE, Quad_Icons64x64_1::SUBSTYLE_Check);
	}

	/**
	 * Create the default disabled Design
	 *
	 * @return static
	 */
	public static function defaultDisabledDesign() {
		return new static(Quad_Icons64x64_1::STYLE, Quad_Icons64x64_1::SUBSTYLE_Check);
	}

	/**
	 * Construct a new CheckBox Design object
	 *
	 * @param string $style    Style name or image url
	 * @param string $subStyle (optional) SubStyle name
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
	 * Set the image url
	 *
	 * @param string $url Image url
	 * @return static
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
	 * @return static
	 */
	public function applyToQuad(Quad $quad) {
		$quad->setImage($this->url);
		$quad->setStyles($this->style, $this->subStyle);
		return $this;
	}

	/**
	 * Get the CheckBox Design string
	 *
	 * @param bool $escaped        (optional) Whether the string should be escaped for the Script
	 * @param bool $addApostrophes (optional) Whether to add apostrophes before and after the text
	 * @return string
	 */
	public function getDesignString($escaped = true, $addApostrophes = true) {
		if ($this->url !== null) {
			$string = $this->url;
		} else {
			$string = $this->style . '|' . $this->subStyle;;
		}
		if ($escaped) {
			return Builder::escapeText($string, $addApostrophes);
		}
		return $string;
	}
}
