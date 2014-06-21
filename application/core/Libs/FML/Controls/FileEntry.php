<?php

namespace FML\Controls;

/**
 * FileEntry Control
 * (CMlFileEntry)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class FileEntry extends Entry {
	/*
	 * Protected properties
	 */
	protected $tagName = 'fileentry';
	protected $folder = null;

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlFileEntry';
	}

	/**
	 * Set the base folder
	 *
	 * @param string $folder Base folder
	 * @return \FML\Controls\FileEntry|static
	 */
	public function setFolder($folder) {
		$this->folder = (string)$folder;
		return $this;
	}

	/**
	 * @see \FML\Entry::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = parent::render($domDocument);
		if ($this->folder) {
			$xmlElement->setAttribute('folder', $this->folder);
		}
		return $xmlElement;
	}
}
