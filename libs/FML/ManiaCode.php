<?php

namespace FML;

use FML\ManiaCode\AddBuddy;
use FML\ManiaCode\AddFavorite;
use FML\ManiaCode\Element;
use FML\ManiaCode\GetSkin;
use FML\ManiaCode\Go_To;
use FML\ManiaCode\InstallMacroblock;
use FML\ManiaCode\InstallMap;
use FML\ManiaCode\InstallPack;
use FML\ManiaCode\InstallReplay;
use FML\ManiaCode\InstallScript;
use FML\ManiaCode\InstallSkin;
use FML\ManiaCode\JoinServer;
use FML\ManiaCode\PlayMap;
use FML\ManiaCode\PlayReplay;
use FML\ManiaCode\ShowMessage;
use FML\ManiaCode\ViewReplay;

/**
 * Class representing a ManiaCode
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ManiaCode
{

    /**
     * @var bool $disableConfirmation Disable the confirmation
     */
    protected $disableConfirmation = null;

    /**
     * @var Element[] $elements ManiaCode Elements
     */
    protected $elements = array();

    /**
     * Create a new ManiaCode
     *
     * @api
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get if the confirmation is disabled
     *
     * @api
     * @return bool
     */
    public function getDisableConfirmation()
    {
        return $this->disableConfirmation;
    }

    /**
     * Disable the showing of the confirmation at the end of the ManiaCode
     *
     * @api
     * @param bool $disableConfirmation If the confirmation should be disabled
     * @return static
     */
    public function setDisableConfirmation($disableConfirmation)
    {
        $this->disableConfirmation = (bool)$disableConfirmation;
        return $this;
    }

    /**
     * Show a message
     *
     * @api
     * @param string $message Message text
     * @return static
     */
    public function addShowMessage($message)
    {
        $messageElement = new ShowMessage($message);
        return $this->addElement($messageElement);
    }

    /**
     * Install a Macroblock
     *
     * @api
     * @param string $name Macroblock name
     * @param string $file Macroblock file
     * @param string $url  Macroblock url
     * @return static
     */
    public function addInstallMacroblock($name, $file, $url)
    {
        $macroblockElement = new InstallMacroblock($name, $file, $url);
        return $this->addElement($macroblockElement);
    }

    /**
     * Install a map
     *
     * @api
     * @param string $name Map name
     * @param string $url  Map url
     * @return static
     */
    public function addInstallMap($name, $url)
    {
        $mapElement = new InstallMap($name, $url);
        return $this->addElement($mapElement);
    }

    /**
     * Play a map
     *
     * @api
     * @param string $name Map name
     * @param string $url  Map url
     * @return static
     */
    public function addPlayMap($name, $url)
    {
        $mapElement = new PlayMap($name, $url);
        return $this->addElement($mapElement);
    }

    /**
     * Install a replay
     *
     * @api
     * @param string $name Replay name
     * @param string $url  Replay url
     * @return static
     */
    public function addInstallReplay($name, $url)
    {
        $replayElement = new InstallReplay($name, $url);
        return $this->addElement($replayElement);
    }

    /**
     * View a replay
     *
     * @api
     * @param string $name Replay name
     * @param string $url  Replay url
     * @return static
     */
    public function addViewReplay($name, $url)
    {
        $replayElement = new ViewReplay($name, $url);
        return $this->addElement($replayElement);
    }

    /**
     * Play a replay
     *
     * @api
     * @param string $name Replay name
     * @param string $url  Replay url
     * @return static
     */
    public function addPlayReplay($name, $url)
    {
        $replayElement = new PlayReplay($name, $url);
        return $this->addElement($replayElement);
    }

    /**
     * Install a skin
     *
     * @api
     * @param string $name Skin name
     * @param string $file Skin file
     * @param string $url  Skin url
     * @return static
     */
    public function addInstallSkin($name, $file, $url)
    {
        $skinElement = new InstallSkin($name, $file, $url);
        return $this->addElement($skinElement);
    }

    /**
     * Get a skin
     *
     * @api
     * @param string $name Skin name
     * @param string $file Skin file
     * @param string $url  Skin url
     * @return static
     */
    public function addGetSkin($name, $file, $url)
    {
        $skinElement = new GetSkin($name, $file, $url);
        return $this->addElement($skinElement);
    }

    /**
     * Add a buddy
     *
     * @api
     * @param string $login Buddy login
     * @return static
     */
    public function addAddBuddy($login)
    {
        $buddyElement = new AddBuddy($login);
        return $this->addElement($buddyElement);
    }

    /**
     * Go to a link
     *
     * @api
     * @param string $link Goto link
     * @return static
     */
    public function addGoto($link)
    {
        $gotoElement = new Go_To($link);
        return $this->addElement($gotoElement);
    }

    /**
     * Join a server
     *
     * @api
     * @param string $loginOrIp (optional) Server login or ip
     * @param int    $port      (optional) Server port
     * @return static
     */
    public function addJoinServer($loginOrIp = null, $port = null)
    {
        $serverElement = new JoinServer($loginOrIp, $port);
        return $this->addElement($serverElement);
    }

    /**
     * Add a server as favorite
     *
     * @api
     * @param string $loginOrIp (optional) Server login or ip
     * @param int    $port      (optional) Server port
     * @return static
     */
    public function addAddFavorite($loginOrIp = null, $port = null)
    {
        $favoriteElement = new AddFavorite($loginOrIp, $port);
        return $this->addElement($favoriteElement);
    }

    /**
     * Install a script
     *
     * @api
     * @param string $name Script name
     * @param string $file Script file
     * @param string $url  Script url
     * @return static
     */
    public function addInstallScript($name, $file, $url)
    {
        $scriptElement = new InstallScript($name, $file, $url);
        return $this->addElement($scriptElement);
    }

    /**
     * Install a title pack
     *
     * @api
     * @param string $name Pack name
     * @param string $file Pack file
     * @param string $url  Pack url
     * @return static
     */
    public function addInstallPack($name, $file, $url)
    {
        $packElement = new InstallPack($name, $file, $url);
        return $this->addElement($packElement);
    }

    /**
     * Get all Elements
     *
     * @api
     * @return Element[]
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Add a ManiaCode Element
     *
     * @api
     * @param Element $element Element to add
     * @return static
     */
    public function addElement(Element $element)
    {
        if (!in_array($element, $this->elements, true)) {
            array_push($this->elements, $element);
        }
        return $this;
    }

    /**
     * Add ManiaCode Elements
     *
     * @api
     * @param Element[] $elements Elements to add
     * @return static
     */
    public function addElements(array $elements)
    {
        foreach ($elements as $element) {
            $this->addElement($element);
        }
        return $this;
    }

    /**
     * Remove all ManiaCode Elements
     *
     * @api
     * @return static
     */
    public function removeAllElements()
    {
        $this->elements = array();
        return $this;
    }

    /**
     * Remove all ManiaCode Elements
     *
     * @api
     * @return static
     * @deprecated Use removeAllElements()
     * @see        ManiaCode::removeAllElements()
     */
    public function removeElements()
    {
        return $this->removeAllElements();
    }

    /**
     * Render the ManiaCode
     *
     * @api
     * @param bool $echo (optional) If the XML text should be echoed and the Content-Type header should be set
     * @return \DOMDocument
     */
    public function render($echo = false)
    {
        $domDocument                = new \DOMDocument("1.0", "utf-8");
        $domDocument->xmlStandalone = true;

        $domElement = $domDocument->createElement("maniacode");
        $domDocument->appendChild($domElement);

        if ($this->disableConfirmation) {
            $domElement->setAttribute("noconfirmation", 1);
        }

        foreach ($this->elements as $element) {
            $childDomElement = $element->render($domDocument);
            $domElement->appendChild($childDomElement);
        }

        if ($echo) {
            header("Content-Type: application/xml; charset=utf-8;");
            echo $domDocument->saveXML();
        }

        return $domDocument;
    }

    /**
     * Get the string representation
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render()
                    ->saveXML();
    }

}
