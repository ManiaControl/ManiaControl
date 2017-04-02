<?php

namespace FML\Elements;

/**
 * Dictionary Element
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Dico
{

    /**
     * Czech language
     *
     * @var string
     */
    const LANG_CZECH = 'cz';

    /**
     * Danish language
     *
     * @var string
     */
    const LANG_DANISH = 'da';

    /**
     * German language
     *
     * @var string
     */
    const LANG_GERMAN = 'de';

    /**
     * English language
     *
     * @var string
     */
    const LANG_ENGLISH = 'en';

    /**
     * Spanish language
     *
     * @var string
     */
    const LANG_SPANISH = 'es';

    /**
     * French language
     *
     * @var string
     */
    const LANG_FRENCH = 'fr';

    /**
     * Hungarian language
     *
     * @var string
     */
    const LANG_HUNGARIAN = 'hu';

    /**
     * Italian language
     *
     * @var string
     */
    const LANG_ITALIAN = 'it';

    /**
     * Japanese language
     *
     * @var string
     */
    const LANG_JAPANESE = 'jp';

    /**
     * Korean language
     *
     * @var string
     */
    const LANG_KOREAN = 'kr';

    /**
     * Norwegian language
     *
     * @var string
     */
    const LANG_NORWEGIAN = 'nb';

    /**
     * Dutch language
     *
     * @var string
     */
    const LANG_DUTCH = 'nl';

    /**
     * Polish language
     *
     * @var string
     */
    const LANG_POLISH = 'pl';

    /**
     * Portuguese language
     *
     * @var string
     */
    const LANG_PORTUGUESE = 'pt';

    /**
     * Brazilian Portuguese language
     *
     * @var string
     */
    const LANG_BRAZILIAN_PORTUGUESE = 'pt_BR';

    /**
     * Romanian language
     *
     * @var string
     */
    const LANG_ROMANIAN = 'ro';

    /**
     * Russian language
     *
     * @var string
     */
    const LANG_RUSSIAN = 'ru';

    /**
     * Slovak language
     *
     * @var string
     */
    const LANG_SLOVAK = 'sk';

    /**
     * Turkish language
     *
     * @var string
     */
    const LANG_TURKISH = 'tr';

    /**
     * Chinese language
     *
     * @var string
     */
    const LANG_CHINESE = 'zh';

    /*
     * Protected properties
     */
    protected $entries = array();

    /**
     * Create a new Dico
     *
     * @api
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get the translatable entry
     *
     * @api
     * @param string $language Language id
     * @param string $entryId  Entry id
     * @return string
     */
    public function getEntry($language, $entryId)
    {
        if (isset($this->entries[$language]) && isset($this->entries[$language][$entryId])) {
            return $this->entries[$language][$entryId];
        }
        return null;
    }

    /**
     * Set the translatable entry for the specific language
     *
     * @api
     * @param string $language   Language id
     * @param string $entryId    Entry id
     * @param string $entryValue Translated entry value
     * @return static
     */
    public function setEntry($language, $entryId, $entryValue)
    {
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
     * Remove entries of the given id
     *
     * @api
     * @param string $entryId  Entry id that should be removed
     * @param string $language (optional) Only remove entry from the given language
     * @return static
     */
    public function removeEntry($entryId, $language = null)
    {
        $entryId = (string)$entryId;
        foreach ($this->entries as $languageKey => $entries) {
            if ($language && $language !== $languageKey) {
                continue;
            }
            if (isset($this->entries[$languageKey][$entryId])) {
                unset($this->entries[$languageKey][$entryId]);
            }
        }
        return $this;
    }

    /**
     * Remove entries of the given language
     *
     * @api
     * @param string $language Language which entries should be removed
     * @return static
     */
    public function removeLanguage($language)
    {
        $language = (string)$language;
        if (isset($this->entries[$language])) {
            unset($this->entries[$language]);
        }
        return $this;
    }

    /**
     * Remove entries
     *
     * @api
     * @return static
     * @deprecated Use removeAllEntries()
     * @see        Dico::removeAllEntries()
     */
    public function removeEntries()
    {
        return $this->removeAllEntries();
    }

    /**
     * Remove all entries
     *
     * @api
     * @return static
     */
    public function removeAllEntries()
    {
        $this->entries = array();
        return $this;
    }

    /**
     * Render the Dico
     *
     * @param \DOMDocument $domDocument DOMDocument for which the Dico should be rendered
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("dico");
        foreach ($this->entries as $language => $entries) {
            $languageElement = $domDocument->createElement("language");
            $languageElement->setAttribute("id", $language);
            foreach ($entries as $entryId => $entryValue) {
                $entryElement = $domDocument->createElement($entryId, $entryValue);
                $languageElement->appendChild($entryElement);
            }
            $domElement->appendChild($languageElement);
        }
        return $domElement;
    }

}
