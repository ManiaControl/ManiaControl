<?php

namespace FML\Controls;

use FML\Types\Renderable;

/**
 * Class representing CMlControl
 *
 * @author steeffeen
 */
abstract class Control implements Renderable {
	/**
	 * Constants
	 */
	const CENTER = 'center';
	const CENTER2 = 'center2';
	const TOP = 'top';
	const RIGHT = 'right';
	const BOTTOM = 'bottom';
	const LEFT = 'left';
	
	/**
	 * Protected properties
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
	 * Construct a new control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		if ($id !== null) {
			$this->setId($id);
		}
	}

	/**
	 * Get control id
	 *
	 * @return string
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set control id
	 *
	 * @param string $id        	
	 * @return \FML\Controls\Control
	 */
	public function setId($id) {
		$this->id = $id;
		return $this;
	}

	/**
	 * Assign an unique id if necessary
	 *
	 * @return \FML\Controls\Control
	 */
	public function assignId() {
		if ($this->getId()) {
			return $this;
		}
		$this->setId(uniqid());
		return $this;
	}

	/**
	 * Set x position
	 *
	 * @param float $x        	
	 * @return \FML\Controls\Control
	 */
	public function setX($x) {
		$this->x = $x;
		return $this;
	}

	/**
	 * Set y position
	 *
	 * @param float $y        	
	 * @return \FML\Controls\Control
	 */
	public function setY($y) {
		$this->y = $y;
		return $this;
	}

	/**
	 * Set z position
	 *
	 * @param float $z        	
	 * @return \FML\Controls\Control
	 */
	public function setZ($z) {
		$this->z = $z;
		return $this;
	}

	/**
	 * Set position
	 *
	 * @param float $x        	
	 * @param float $y        	
	 * @param float $z        	
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
	 * Set width
	 *
	 * @param float $width        	
	 * @return \FML\Controls\Control
	 */
	public function setWidth($width) {
		$this->width = $width;
		return $this;
	}

	/**
	 * Set height
	 *
	 * @param float $height        	
	 * @return \FML\Controls\Control
	 */
	public function setHeight($height) {
		$this->height = $height;
		return $this;
	}

	/**
	 * Set size
	 *
	 * @param float $width        	
	 * @param float $height        	
	 * @return \FML\Controls\Control
	 */
	public function setSize($width, $height) {
		$this->setWidth($width);
		$this->setHeight($height);
		return $this;
	}

	/**
	 * Set horizontal alignment
	 *
	 * @param string $hAlign        	
	 * @return \FML\Controls\Control
	 */
	public function setHAlign($hAlign) {
		$this->hAlign = $hAlign;
		return $this;
	}

	/**
	 * Set vertical alignment
	 *
	 * @param string $vAlign        	
	 * @return \FML\Controls\Control
	 */
	public function setVAlign($vAlign) {
		$this->vAlign = $vAlign;
		return $this;
	}

	/**
	 * Set horizontal and vertical alignment
	 *
	 * @param string $hAlign
	 * @param string $vAlign        	
	 * @return \FML\Controls\Control
	 */
	public function setAlign($hAlign, $vAlign) {
		$this->setHAlign($hAlign);
		$this->setVAlign($vAlign);
		return $this;
	}

	/**
	 * Set scale
	 *
	 * @param float $scale        	
	 * @return \FML\Controls\Control
	 */
	public function setScale($scale) {
		$this->scale = $scale;
		return $this;
	}

	/**
	 * Set visible
	 *
	 * @param bool $visible        	
	 * @return \FML\Controls\Control
	 */
	public function setVisible($visible) {
		$this->hidden = ($visible ? 0 : 1);
		return $this;
	}

	/**
	 * Add class name
	 *
	 * @param string $class        	
	 * @return \FML\Controls\Control
	 */
	public function addClass($class) {
		array_push($this->classes, $class);
		return $this;
	}

	/**
	 *
	 * @see \FML\Types\Renderable::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = $domDocument->createElement($this->tagName);
		if ($this->id) {
			$xml->setAttribute('id', $this->id);
		}
		if ($this->x !== 0. || $this->y !== 0. || $this->z !== 0.) {
			$xml->setAttribute('posn', "{$this->x} {$this->y} {$this->z}");
		}
		if ($this->width >= 0. || $this->height >= 0.) {
			$xml->setAttribute('sizen', "{$this->width} {$this->height}");
		}
		if (get_class($this) !== Frame::getClass()) {
			if ($this->hAlign) {
				$xml->setAttribute('halign', $this->hAlign);
			}
			if ($this->vAlign) {
				$xml->setAttribute('valign', $this->vAlign);
			}
		}
		if ($this->scale !== 1.) {
			$xml->setAttribute('scale', $this->scale);
		}
		if ($this->hidden) {
			$xml->setAttribute('hidden', $this->hidden);
		}
		$classes = '';
		foreach ($this->classes as $class) {
			$classes .= $class . ' ';
		}
		if ($classes) {
			$xml->setAttribute('class', $classes);
		}
		return $xml;
	}
}
