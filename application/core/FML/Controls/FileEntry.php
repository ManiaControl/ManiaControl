<?php

namespace FML\Controls;

/**
 * Class representing CMlFileEntry
 *
 * @author steeffeen
 */
class FileEntry extends Entry {
	/**
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
	 * Set Folder
	 *
	 * @param string $folder Base Folder
	 * @return \FML\Controls\FileEntry
	 */
	public function setFolder($folder) {
		$this->folder = (string) $folder;
		return $this;
	}

	/**
	 *
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
