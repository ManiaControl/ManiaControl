<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element showing a Message
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class ShowMessage implements Element
{

    /**
     * @var string $message Message text
     */
    protected $message = null;

    /**
     * Create a new ShowMessage Element
     *
     * @api
     * @param string $message (optional) Message text
     * @return static
     */
    public static function create($message = null)
    {
        return new static($message);
    }

    /**
     * Construct a new ShowMessage Element
     *
     * @api
     * @param string $message (optional) Message text
     */
    public function __construct($message = null)
    {
        if ($message) {
            $this->setMessage($message);
        }
    }

    /**
     * Get the message text
     *
     * @api
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set the message text
     *
     * @api
     * @param string $message Message text
     * @return static
     */
    public function setMessage($message)
    {
        $this->message = (string)$message;
        return $this;
    }

    /**
     * @see Element::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("show_message");

        $messageElement = $domDocument->createElement("message", $this->message);
        $domElement->appendChild($messageElement);

        return $domElement;
    }

}
