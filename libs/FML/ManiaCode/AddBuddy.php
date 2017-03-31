<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element adding a buddy
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AddBuddy implements Element
{

    /**
     * @var string $login Buddy login
     */
    protected $login = null;

    /**
     * Create a new AddBuddy Element
     *
     * @api
     * @param string $login (optional) Buddy login
     * @return static
     */
    public static function create($login = null)
    {
        return new static($login);
    }

    /**
     * Construct a new AddBuddy Element
     *
     * @api
     * @param string $login (optional) Buddy login
     */
    public function __construct($login = null)
    {
        if ($login) {
            $this->setLogin($login);
        }
    }

    /**
     * Get the buddy login
     *
     * @api
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set the buddy login
     *
     * @api
     * @param string $login Buddy login
     * @return static
     */
    public function setLogin($login)
    {
        $this->login = (string)$login;
        return $this;
    }

    /**
     * @see Element::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("add_buddy");

        $loginElement = $domDocument->createElement("login", $this->login);
        $domElement->appendChild($loginElement);

        return $domElement;
    }

}
