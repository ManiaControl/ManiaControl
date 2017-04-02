<?php

namespace FML;

/**
 * Class holding several ManiaLinks at once
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaLinks
{

    /**
     * @var ManiaLink[] $children ManiaLinks children
     */
    protected $children = array();

    /**
     * @var CustomUI $customUI Custom UI
     */
    protected $customUI = null;

    /**
     * Create a new ManiaLinks object
     *
     * @api
     * @param ManiaLink[] $children ManiaLink children
     * @return static
     */
    public static function create(array $children = null)
    {
        return new static($children);
    }

    /**
     * Construct a new ManiaLinks object
     *
     * @api
     * @param ManiaLink[] $children ManiaLink children
     */
    public function __construct(array $children = null)
    {
        if ($children) {
            $this->setChildren($children);
        }
    }

    /**
     * Get all child ManiaLinks
     *
     * @api
     * @return ManiaLink[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add a child ManiaLink
     *
     * @api
     * @param ManiaLink $child Child ManiaLink
     * @return static
     * @deprecated Use addChild()
     * @see        ManiaLinks::addChild()
     */
    public function add(ManiaLink $child)
    {
        return $this->addChild($child);
    }

    /**
     * Add a child ManiaLink
     *
     * @api
     * @param ManiaLink $child Child ManiaLink
     * @return static
     */
    public function addChild(ManiaLink $child)
    {
        if (!in_array($child, $this->children, true)) {
            array_push($this->children, $child);
        }
        return $this;
    }

    /**
     * Add child ManiaLinks
     *
     * @api
     * @param ManiaLink[] $children Child ManiaLinks
     * @return static
     */
    public function addChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
        return $this;
    }

    /**
     * Set ManiaLink children
     *
     * @api
     * @param ManiaLink[] $children ManiaLink children
     * @return static
     */
    public function setChildren(array $children)
    {
        return $this->removeAllChildren()
                    ->addChildren($children);
    }

    /**
     * Remove all child ManiaLinks
     *
     * @api
     * @return static
     */
    public function removeAllChildren()
    {
        $this->children = array();
        return $this;
    }

    /**
     * Remove all child ManiaLinks
     *
     * @api
     * @return static
     * @deprecated Use removeAllChildren()
     * @see        ManiaLinks::removeAllChildren()
     */
    public function removeChildren()
    {
        return $this->removeAllChildren();
    }

    /**
     * Get the CustomUI
     *
     * @api
     * @param bool $createIfEmpty (optional) If the Custom UI should be created if it doesn't exist yet
     * @return CustomUI
     */
    public function getCustomUI($createIfEmpty = true)
    {
        if (!$this->customUI && $createIfEmpty) {
            $this->setCustomUI(new CustomUI());
        }
        return $this->customUI;
    }

    /**
     * Set the CustomUI
     *
     * @api
     * @param CustomUI $customUI CustomUI object
     * @return static
     */
    public function setCustomUI(CustomUI $customUI = null)
    {
        $this->customUI = $customUI;
        return $this;
    }

    /**
     * Render the ManiaLinks object
     *
     * @param bool (optional) $echo If the XML text should be echoed and the Content-Type header should be set
     * @return \DOMDocument
     */
    public function render($echo = false)
    {
        $domDocument                = new \DOMDocument("1.0", "utf-8");
        $domDocument->xmlStandalone = true;
        $maniaLinks                 = $domDocument->createElement("manialinks");
        $domDocument->appendChild($maniaLinks);

        foreach ($this->children as $child) {
            $childXml = $child->render(false, $domDocument);
            $maniaLinks->appendChild($childXml);
        }

        if ($this->customUI) {
            $customUIElement = $this->customUI->render($domDocument);
            $maniaLinks->appendChild($customUIElement);
        }

        if ($echo) {
            header("Content-Type: application/xml; charset=utf-8;");
            echo $domDocument->saveXML();
        }

        return $domDocument;
    }

    /**
     * Get string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render()
                    ->saveXML();
    }

}
