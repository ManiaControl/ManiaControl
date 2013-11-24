<?php

namespace FML\Controls;

/**
 * Class representing CMlFileEntry
 *
 * @author steeffeen
 */
class FileEntry extends Entry {
	/**
	 * Protected properties
	 */
	protected $folder = '';

	/**
	 * Construct a new fileentry control
	 *
	 * @param string $id        	
	 */
	public function __construct($id = null) {
		parent::__construct($id);
		$this->tagName = 'fileentry';
	}

	/**
	 * Set folder
	 *
	 * @param string $folder        	
	 * @return \FML\Controls\FileEntry
	 */
	public function setFolder($folder) {
		$this->folder = $folder;
		return $this;
	}

	/**
	 *
	 * @see \FML\Entry::render()
	 */
	public function render(\DOMDocument $domDocument) {
		$xml = parent::render($domDocument);
		$xml->setAttribute('folder', $this->folder);
		return $xml;
	}
}

?>
