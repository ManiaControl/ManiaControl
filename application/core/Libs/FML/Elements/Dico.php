<?php

namespace FML\Elements;

/**
 * Dictionary Element
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2014 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Dico {
	/**
	 * Czech Language
	 *
	 * @var string
	 */
	const LANG_CZECH = 'cz';

	/**
	 * Danish Language
	 *
	 * @var string
	 */
	const LANG_DANISH = 'da';

	/**
	 * German Language
	 *
	 * @var string
	 */
	const LANG_GERMAN = 'de';

	/**
	 * English Language
	 *
	 * @var string
	 */
	const LANG_ENGLISH = 'en';

	/**
	 * Spanish Language
	 *
	 * @var string
	 */
	const LANG_SPANISH = 'es';

	/**
	 * French Language
	 *
	 * @var string
	 */
	const LANG_FRENCH = 'fr';

	/**
	 * Hungarian Language
	 *
	 * @var string
	 */
	const LANG_HUNGARIAN = 'hu';

	/**
	 * Italian Language
	 *
	 * @var string
	 */
	const LANG_ITALIAN = 'it';

	/**
	 * Japanese Language
	 *
	 * @var string
	 */
	const LANG_JAPANESE = 'jp';

	/**
	 * Korean Language
	 *
	 * @var string
	 */
	const LANG_KOREAN = 'kr';

	/**
	 * Norwegian Language
	 *
	 * @var string
	 */
	const LANG_NORWEGIAN = 'nb';

	/**
	 * Dutch Language
	 *
	 * @var string
	 */
	const LANG_DUTCH = 'nl';

	/**
	 * Polish Language
	 *
	 * @var string
	 */
	const LANG_POLISH = 'pl';

	/**
	 * Portuguese Language
	 *
	 * @var string
	 */
	const LANG_PORTUGUESE = 'pt';

	/**
	 * Brazilian Portuguese Language
	 *
	 * @var string
	 */
	const LANG_BRAZILIAN_PORTUGUESE = 'pt_BR';

	/**
	 * Romanian Language
	 *
	 * @var string
	 */
	const LANG_ROMANIAN = 'ro';

	/**
	 * Russian Language
	 *
	 * @var string
	 */
	const LANG_RUSSIAN = 'ru';

	/**
	 * Slovak Language
	 *
	 * @var string
	 */
	const LANG_SLOVAK = 'sk';

	/**
	 * Turkish Language
	 *
	 * @var string
	 */
	const LANG_TURKISH = 'tr';

	/**
	 * Chinese Language
	 *
	 * @var string
	 */
	const LANG_CHINESE = 'zh';

	/*
	 * Protected Properties
	 */
	protected $tagName = 'dico';
	protected $entries = array();

	/**
	 * Create a new Dictionary Object
	 *
	 * @return \FML\Elements\Dico
	 */
	public static function create() {
		$dico = new Dico();
		return $dico;
	}

	/**
	 * Construct a new Dictionary Object
	 */
	public function __construct() {
	}

	/**
	 * Set the translatable Entry for the specific Language
	 *
	 * @param string $language   Language Id
	 * @param string $entryId    Entry Id
	 * @param string $entryValue Translated Entry Value
	 * @return \FML\Elements\Dico
	 */
	public function setEntry($language, $entryId, $entryValue) {
		$language   = (string)$language;
		$entryId    = (string)$entryId;
		$entryValue = (string)$entryValue;
		if (!isset($this->entries[$language]) && $entryValue) {
			$this->entries[$language] = array();
		}
		if ($entryValue) {
			$this->entries[$language][$entryId] = $entryValue;
		} else {
			if (isset($this->entries[$language][$entryId])) {
				unset($this->entries[$language][$entryId]);
			}
		}
		return $this;
	}

	/**
	 * Remove Entries of the given Id
	 *
	 * @param string $entryId  Entry Id that should be removed
	 * @param string $language (optional) Only remove Entries of the given Language
	 * @return \FML\Elements\Dico
	 */
	public function removeEntry($entryId, $language = null) {
		$entryId = (string)$entryId;
		if ($language) {
			$language = (string)$language;
			if (isset($this->entries[$language])) {
				unset($this->entries[$language][$entryId]);
			}
		} else {
			foreach ($this->entries as $language => $entries) {
				if (isset($entries[$entryId])) {
					unset($entries[$language][$entryId]);
				}
			}
		}
		return $this;
	}

	/**
	 * Remove Entries of the given Language
	 *
	 * @param string $language Language of which all Entries should be removed
	 * @param string $entryId  (optional) Only remove the given Entry Id
	 * @return \FML\Elements\Dico
	 */
	public function removeLanguage($language, $entryId = null) {
		$language = (string)$language;
		if (isset($this->entries[$language])) {
			if ($entryId) {
				$entryId = (string)$entryId;
				unset($this->entries[$language][$entryId]);
			} else {
				unset($this->entries[$language]);
			}
		}
		return $this;
	}

	/**
	 * Remove all Entries from the Dictionary
	 *
	 * @return \FML\Elements\Dico
	 */
	public function removeEntries() {
		$this->entries = array();
		return $this;
	}

	/**
	 * Render the Dico XML Element
	 *
	 * @param \DOMDocument $domDocument DomDocument for which the Dico XML Element should be rendered
	 * @return \DOMElement
	 */
	public function render(\DOMDocument $domDocument) {
		$xmlElement = $domDocument->createElement($this->tagName);
		foreach ($this->entries as $language => $entries) {
			$languageElement = $domDocument->createElement('language');
			$languageElement->setAttribute('id', $language);
			foreach ($entries as $entryId => $entryValue) {
				$entryElement = $domDocument->createElement($entryId, $entryValue);
				$languageElement->appendChild($entryElement);
			}
			$xmlElement->appendChild($languageElement);
		}
		return $xmlElement;
	}
}
