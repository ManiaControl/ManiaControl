<?php

namespace FML\Stylesheet;

/**
 * Class representing the ManiaLinks Stylesheet
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Stylesheet {
	/*
	 * Protected Properties
	 */
	protected $tagName = 'stylesheet';
	protected $styles3d = array();
	/** @var Mood $mood */
	protected $mood = null;

	/**
	 * Create a new Stylesheet Object
	 *
	 * @return \FML\Stylesheet\Stylesheet
	 */
	public static function create() {
		$stylesheet = new Stylesheet();
		return $stylesheet;
	}

	/**
	 * Construct a new Stylesheet Object
	 */
	public function __construct() {
	}

	/**
	 * Add a new Style3d
	 *
	 * @param Style3d $style3d The Style3d to add
	 * @return \FML\Stylesheet\Stylesheet
	 */
	public function addStyle3d(Style3d $style3d) {
		if (!in_array($style3d, $this->styles3d, true)) {
			array_push($this->styles3d, $style3d);
		}
		return $this;
	}

	/**
	 * Remove all Styles
	 *
	 * @return \FML\Stylesheet\Stylesheet
	 */
	public function removeStyles() {
		$this->styles3d = array();
		return $this;
	}

	/**
	 * Set the Mood Object of the Stylesheet
	 *
	 * @param Mood $mood Mood Object
	 * @return \FML\Stylesheet\Stylesheet
	 */
	public function setMood(Mood $mood) {
		$this->mood = $mood;
		return $this;
	}

	/**
	 * Get the Mood Object
	 *
	 * @param bool $createIfEmpty (optional) Whether the Mood Object should be created if it's not set yet
	 * @return \FML\Stylesheet\Mood
	 */
	public function getMood($createIfEmpty = true) {
		if (!$this->mood && $createIfEmpty) {
			$this->setMood(new Mood());
		}
		return $this->mood;
	}

	/**
	 * Render the Stylesheet XML Element
	 *
	 * @param \DOMDocument $domDocument DomDocument for which the Stylesheet XML Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$stylesheetXml = $domDocument->createElement($this->tagName);
		if ($this->styles3d) {
			$stylesXml = $domDocument->createElement('frame3dstyles');
			$stylesheetXml->appendChild($stylesXml);
			foreach ($this->styles3d as $style3d) {
				/** @var Style3d $style3d */
				$style3dXml = $style3d->render($domDocument);
				$stylesXml->appendChild($style3dXml);
			}
		}
		if ($this->mood) {
			$moodXml = $this->mood->render($domDocument);
			$stylesheetXml->appendChild($moodXml);
		}
		return $stylesheetXml;
	}
}
