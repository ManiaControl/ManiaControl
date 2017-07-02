<?php

namespace FML\XmlRpc;

/**
 * Class representing common UI Properties
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class UIProperties
{

    /**
     * @var array $chatProperties Chat properties
     */
    protected $chatProperties = array();

    /**
     * @var array $chatAvatarProperties Chat avatar properties
     */
    protected $chatAvatarProperties = array();

    /**
     * @var array $mapInfoProperties Map info properties
     */
    protected $mapInfoProperties = array();

    /**
     * @var array $countdownProperties Countdown properties
     */
    protected $countdownProperties = array();

    /**
     * @var array $goProperties Go! properties
     */
    protected $goProperties = array();

    /**
     * @var array $endMapLadderRecapProperties End map ladder recap properties
     */
    protected $endMapLadderRecapProperties = array();

    /**
     * @var array $scoresTableProperties Scores table properties
     */
    protected $scoresTableProperties = array();

    /**
     * Create new UI Properties
     *
     * @api
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Get the chat visibility
     *
     * @api
     * @return bool
     */
    public function getChatVisible()
    {
        return $this->getVisibleProperty($this->chatProperties);
    }

    /**
     * Set the chat visibility
     *
     * @api
     * @param bool $visible If the chat should be visible
     * @return static
     */
    public function setChatVisible($visible)
    {
        $this->setVisibleProperty($this->chatProperties, $visible);
        return $this;
    }

    /**
     * Get the chat offset
     *
     * @api
     * @return string
     */
    public function getChatOffset()
    {
        return $this->getProperty($this->chatProperties, "offset");
    }

    /**
     * Set the chat offset
     *
     * @api
     * @param float $offsetX X offset
     * @param float $offsetY Y offset
     * @return static
     */
    public function setChatOffset($offsetX, $offsetY)
    {
        $offset = array((float)$offsetX, (float)$offsetY);
        $this->setProperty($this->chatProperties, "offset", implode(" ", $offset));
        return $this;
    }

    /**
     * Get the chat line count
     *
     * @api
     * @return int
     */
    public function getChatLineCount()
    {
        return $this->getProperty($this->chatProperties, "linecount");
    }

    /**
     * Set the chat line count
     *
     * @api
     * @param int $lineCount Line count
     * @return static
     */
    public function setChatLineCount($lineCount)
    {
        $this->setProperty($this->chatProperties, "linecount", (int)$lineCount);
        return $this;
    }

    /**
     * Get the chat avatar visibility
     *
     * @api
     * @return bool
     */
    public function getChatAvatarVisible()
    {
        return $this->getVisibleProperty($this->chatAvatarProperties);
    }

    /**
     * Set the chat avatar visibility
     *
     * @api
     * @param bool $visible If the chat avatar should be visible
     * @return static
     */
    public function setChatAvatarVisible($visible)
    {
        $this->setVisibleProperty($this->chatAvatarProperties, $visible);
        return $this;
    }

    /**
     * Get the map info visibility
     *
     * @api
     * @return bool
     */
    public function getMapInfoVisible()
    {
        return $this->getVisibleProperty($this->mapInfoProperties);
    }

    /**
     * Set the map info visibility
     *
     * @api
     * @param bool $visible If the map info should be visible
     * @return static
     */
    public function setMapInfoVisible($visible)
    {
        $this->setVisibleProperty($this->mapInfoProperties, $visible);
        return $this;
    }

    /**
     * Get the map info position
     *
     * @api
     * @return string
     */
    public function getMapInfoPosition()
    {
        return $this->getPositionProperty($this->mapInfoProperties);
    }

    /**
     * Set the map info position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setMapInfoPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->mapInfoProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the countdown visibility
     *
     * @api
     * @return bool
     */
    public function getCountdownVisible()
    {
        return $this->getVisibleProperty($this->countdownProperties);
    }

    /**
     * Set the countdown visibility
     *
     * @api
     * @param bool $visible If the countdown should be visible
     * @return static
     */
    public function setCountdownVisible($visible)
    {
        $this->setVisibleProperty($this->countdownProperties, $visible);
        return $this;
    }

    /**
     * Get the countdown position
     *
     * @api
     * @return string
     */
    public function getCountdownPosition()
    {
        return $this->getPositionProperty($this->countdownProperties);
    }

    /**
     * Set the countdown position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setCountdownPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->countdownProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the Go! visibility
     *
     * @api
     * @return bool
     */
    public function getGoVisible()
    {
        return $this->getVisibleProperty($this->goProperties);
    }

    /**
     * Set the Go! visibility
     *
     * @api
     * @param bool $visible If Go! should be visible
     * @return static
     */
    public function setGoVisible($visible)
    {
        $this->setVisibleProperty($this->goProperties, $visible);
        return $this;
    }

    /**
     * Get the end map ladder recap visibility
     *
     * @api
     * @return bool
     */
    public function getEndMapLadderRecapVisible()
    {
        return $this->getVisibleProperty($this->endMapLadderRecapProperties);
    }

    /**
     * Set the end map ladder recap visibility
     *
     * @api
     * @param bool $visible If the end map ladder recap should be visible
     * @return static
     */
    public function setEndMapLadderRecapVisible($visible)
    {
        $this->setVisibleProperty($this->endMapLadderRecapProperties, $visible);
        return $this;
    }

    /**
     * Get the scores table alt visibility
     *
     * @api
     * @return bool
     */
    public function getScoresTableAltVisible()
    {
        return $this->getProperty($this->scoresTableProperties, "alt_visible");
    }

    /**
     * Set the scores table alt visibility
     *
     * @api
     * @param bool $altVisible If the scores table should be visible on alt
     * @return static
     */
    public function setScoresTableAltVisible($altVisible)
    {
        $this->setProperty($this->scoresTableProperties, "alt_visible", (bool)$altVisible);
        return $this;
    }

    /**
     * Render the UI Properties standalone
     *
     * @return \DOMDocument
     */
    public function renderStandalone()
    {
        $domDocument                = new \DOMDocument("1.0", "utf-8");
        $domDocument->xmlStandalone = true;

        $domElement = $domDocument->createElement("ui_properties");
        $domDocument->appendChild($domElement);

        $allProperties = $this->getProperties();
        foreach ($allProperties as $property => $propertySettings) {
            if (!$propertySettings) {
                continue;
            }

            $propertyDomElement = $domDocument->createElement($property);
            $domElement->appendChild($propertyDomElement);

            foreach ($propertySettings as $settingName => $settingValue) {
                $settingValueString = (is_string($settingValue) ? $settingValue : var_export($settingValue, true));
                $propertyDomElement->setAttribute($settingName, $settingValueString);
            }
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
        return $this->renderStandalone()
                    ->saveXML();
    }

    /**
     * Get associative array of all properties
     *
     * @return array
     */
    protected function getProperties()
    {
        return array(
            "chat" => $this->chatProperties,
            "chat_avatar" => $this->chatAvatarProperties,
            "map_info" => $this->mapInfoProperties,
            "countdown" => $this->countdownProperties,
            "go" => $this->goProperties,
            "endmap_ladder_recap" => $this->endMapLadderRecapProperties,
            "scorestable" => $this->scoresTableProperties
        );
    }

    /**
     * Get a property value if it's set
     *
     * @param array  $properties Properties array
     * @param string $name       Property name
     * @return mixed
     */
    protected function getProperty(array $properties, $name)
    {
        return (isset($properties[$name]) ? $properties[$name] : null);
    }

    /**
     * Set a property value
     *
     * @param array  $properties Properties array
     * @param string $name       Property name
     * @param mixed  $value      Property value
     * @return static
     */
    protected function setProperty(array &$properties, $name, $value)
    {
        $properties[$name] = $value;
        return $this;
    }

    /**
     * Set the Visible property value
     *
     * @param array $properties Properties array
     * @return bool
     */
    protected function getVisibleProperty(array &$properties)
    {
        return $this->getProperty($properties, "visible");
    }

    /**
     * Set the Visible property value
     *
     * @param array $properties Properties array
     * @param bool  $visible    Visibility value
     * @return static
     */
    protected function setVisibleProperty(array &$properties, $visible)
    {
        $this->setProperty($properties, "visible", (bool)$visible);
        return $this;
    }

    /**
     * Get the Position property value
     *
     * @param array $properties Properties array
     * @return string
     */
    protected function getPositionProperty(array &$properties)
    {
        return $this->getProperty($properties, "pos");
    }

    /**
     * Set the Position property value
     *
     * @param array $properties Properties array
     * @param float $positionX  X position
     * @param float $positionY  Y position
     * @param float $positionZ  (optional) Z position (Z-index)
     * @return static
     */
    protected function setPositionProperty(array &$properties, $positionX, $positionY, $positionZ = null)
    {
        $position = array((float)$positionX, (float)$positionY);
        if ($positionZ) {
            array_push($position, (float)$positionZ);
        }
        $this->setProperty($properties, "pos", implode(" ", $position));
        return $this;
    }

}
