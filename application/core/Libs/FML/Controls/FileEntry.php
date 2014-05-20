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
	 * Protected Properties
	 */
	protected $folder = '';

	/**
	 * Construct a new FileEntry Control
	 *
	 * @param string $id (optional) Control Id
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'fileentry';
	}

	/**
	 * Create a new FileEntry Control
	 *
	 * @param string $id (optional) Control Id
	 * @return \FML\Controls\FileEntry
	 */
	public static function create($id = null) {
		$fileEntry = new FileEntry($id);
		return $fileEntry;
	}

	/**
	 * @see \FML\Controls\Control::getManiaScriptClass()
	 */
	public function getManiaScriptClass() {
		return 'CMlFileEntry';
	}

	/**
	 * Set Folder
	 *
	 * @param string $folder Base Folder
	 * @return \FML\Controls\FileEntry
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
