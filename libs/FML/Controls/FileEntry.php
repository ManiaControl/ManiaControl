<?php

namespace FML\Controls;

/**
 * FileEntry Control
 * (CMlFileEntry)
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class FileEntry extends Entry
{

    /**
     * @var string $folder Folder
     */
    protected $folder = null;

    /**
     * Get the folder
     *
     * @api
     * @return string
     */
    public function getFolder()
    {
        return $this->folder;
    }

    /**
     * Set the folder
     *
     * @api
     * @param string $folder Base folder
     * @return static
     */
    public function setFolder($folder)
    {
        $this->folder = (string)$folder;
        return $this;
    }

    /**
     * @see Control::getTagName()
     */
    public function getTagName()
    {
        return "fileentry";
    }

    /**
     * @see Control::getManiaScriptClass()
     */
    public function getManiaScriptClass()
    {
        return "CMlFileEntry";
    }

    /**
     * @see Renderable::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = parent::render($domDocument);
        if ($this->folder) {
            $domElement->setAttribute("folder", $this->folder);
        }
        return $domElement;
    }

}
