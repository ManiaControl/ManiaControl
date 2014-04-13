<?php

namespace FML\Controls;

use FML\Types\Renderable;

/**
 * Base Control
 * (CMlControl)
 *
 * @author steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class Control implements Renderable {
	/*
	 * Constants
	 */
	const CENTER = 'center';
	const CENTER2 = 'center2';
	const TOP = 'top';
	const RIGHT = 'right';
	const BOTTOM = 'bottom';
	const LEFT = 'left';
	
	/*
	 * Static Properties
	 */
	protected static $currentIndex = 0;
	
	/*
	 * Protected Properties
	 */
	protected $tagName = 'control';
	protected $id = '';
	protected $x = 0.;
	protected $y = 0.;
	protected $z = 0.;
	protected $width = -1.;
	protected $height = -1.;
	protected $hAlign = self::CENTER;
	protected $vAlign = self::CENTER2;
	protected $scale = 1.;
	protected $hidden = 0;
	protected $classes = array();

	/**
	 * Construct a new Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Get Control Id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set Control Id
	 *
	 * @param string $id Control Id
	 * @return \FML\Controls\Control
	 */
	public function setId($id) {
		$this->id = (string) $id;
		return $this;
	}

	/**
	 * Check Id for dangerous Characters and assign a unique Id if necessary
	 *
	 * @return \FML\Controls\Control
	 */
	public function checkId() {
		if (!$this->getId()) {
			$this->setId('FML_ID_' . self::$currentIndex);
			self::$currentIndex++;
			return $this;
		}
		$dangerousCharacters = array(' ', '	', '.', '|', '-', PHP_EOL);
		$idCharacters = str_split($this->getId());
		$danger = false;
		foreach ($idCharacters as $character) {
			if (!in_array($character, $dangerousCharacters)) continue;
			$danger = true;
			break;
		}
		if ($danger) {
			trigger_error("Please don't use special Characters in Ids, they might cause Problems! (I stripped them for You.)");
			$id = str_ireplace($dangerousCharacters, '', $this->getId());
			$this->setId($id);
		}
		return $this;
	}

	/**
	 * Set X Position
	 *
	 * @param float $x Horizontal Position
	 * @return \FML\Controls\Control
	 */
	public function setX($x) {
		$this->x = (float) $x;
		return $this;
	}

	/**
	 * Set Y Position
	 *
	 * @param float $y Vertical Position
	 * @return \FML\Controls\Control
	 */
	public function setY($y) {
		$this->y = (float) $y;
		return $this;
	}

	/**
	 * Set Z Position
	 *
	 * @param float $z Depth
	 * @return \FML\Controls\Control
	 */
	public function setZ($z) {
		$this->z = (float) $z;
		return $this;
	}

	/**
	 * Set Control Position
	 *
	 * @param float $x Horizontal Position
	 * @param float $y Vertical Position
	 * @param float $z (optional) Depth
	 * @return \FML\Controls\Control
	 */
	public function setPosition($x, $y, $z = null) {
		$this->setX($x);
		$this->setY($y);
		if ($z !== null) {
			$this->setZ($z);
		}
		return $this;
	}

	/**
	 * Set Control Width
	 *
	 * @param float $width Control Width
	 * @return \FML\Controls\Control
	 */
	public function setWidth($width) {
		$this->width = (float) $width;
		return $this;
	}

	/**
	 * Set Control Height
	 *
	 * @param float $height Control Height
	 * @return \FML\Controls\Control
	 */
	public function setHeight($height) {
		$this->height = (float) $height;
		return $this;
	}

	/**
	 * Set Control Size
	 *
	 * @param float $width Control Width
	 * @param float $height Control Height
	 * @return \FML\Controls\Control
	 */
	public function setSize($width, $height) {
		$this->setWidth($width);
		$this->setHeight($height);
		return $this;
	}

	/**
	 * Set Horizontal Alignment
	 *
	 * @param string $hAlign Horizontal Alignment
	 * @return \FML\Controls\Control
	 */
	public function setHAlign($hAlign) {
		$this->hAlign = (string) $hAlign;
		return $this;
	}

	/**
	 * Set Vertical Alignment
	 *
	 * @param string $vAlign Vertical Alignment
	 * @return \FML\Controls\Control
	 */
	public function setVAlign($vAlign) {
		$this->vAlign = (string) $vAlign;
		return $this;
	}

	/**
	 * Set Horizontal and Vertical Alignment
	 *
	 * @param string $hAlign Horizontal Alignment
	 * @param string $vAlign Vertical Alignment
	 * @return \FML\Controls\Control
	 */
	public function setAlign($hAlign, $vAlign) {
		$this->setHAlign($hAlign);
		$this->setVAlign($vAlign);
		return $this;
	}

	/**
	 * Set Control Scale
	 *
	 * @param float $scale Control Scale
	 * @return \FML\Controls\Control
	 */
	public function setScale($scale) {
		$this->scale = (float) $scale;
		return $this;
	}

	/**
	 * Set Visibility
	 *
	 * @param bool $visible Whether Control should be visible
	 * @return \FML\Controls\Control
	 */
	public function setVisible($visible) {
		$this->hidden = ($visible ? 0 : 1);
		return $this;
	}

	/**
	 * Add new Class Name
	 *
	 * @param string $class Class Name
	 * @return \FML\Controls\Control
	 */
	public function addClass($class) {
		$class = (string) $class;
		if (!in_array($class, $this->classes)) {
			array_push($this->classes, $class);
		}
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		if ($this->id) {
			$xmlElement->setAttribute('id', $this->id);
		}
		if ($this->x != 0. || $this->y != 0. || $this->z != 0.) {
			$xmlElement->setAttribute('posn', "{$this->x} {$this->y} {$this->z}");
		}
		if ($this->width >= 0. || $this->height >= 0.) {
			$xmlElement->setAttribute('sizen', "{$this->width} {$this->height}");
		}
		if ($this->hAlign) {
			$xmlElement->setAttribute('halign', $this->hAlign);
		}
		if ($this->vAlign) {
			$xmlElement->setAttribute('valign', $this->vAlign);
		}
		if ($this->scale != 1.) {
			$xmlElement->setAttribute('scale', $this->scale);
		}
		if ($this->hidden) {
			$xmlElement->setAttribute('hidden', $this->hidden);
		}
		if (!empty($this->classes)) {
			$classes = '';
			foreach ($this->classes as $class) {
				$classes .= $class . ' ';
			}
			$xmlElement->setAttribute('class', $classes);
		}
		return $xmlElement;
	}
}
