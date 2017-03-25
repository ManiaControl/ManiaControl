<?php

namespace FML\ManiaCode;

/**
 * ManiaCode Element adding a server as favorite
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class AddFavorite implements Element
{

    /**
     * @var string $login Server login
     */
    protected $login = null;

    /**
     * @var string $ip Server ip
     */
    protected $ip = null;

    /**
     * @var int $port Server port
     */
    protected $port = null;

    /**
     * Create a new AddFavorite Element
     *
     * @api
     * @param string $loginOrIp (optional) Server login or ip
     * @param int    $port      (optional) Server port
     * @return static
     */
    public static function create($loginOrIp = null, $port = null)
    {
        return new static($loginOrIp, $port);
    }

    /**
     * Construct a new AddFavorite Element
     *
     * @api
     * @param string $loginOrIp (optional) Server login or ip
     * @param int    $port      (optional) Server port
     */
    public function __construct($loginOrIp = null, $port = null)
    {
        if ($port) {
            $this->setIp($loginOrIp, $port);
        } elseif ($loginOrIp) {
            $this->setLogin($loginOrIp);
        }
    }

    /**
     * Get the server login
     *
     * @api
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set the server login
     *
     * @api
     * @param string $login Server login
     * @return static
     */
    public function setLogin($login)
    {
        $this->login = (string)$login;
        $this->ip    = null;
        $this->port  = null;
        return $this;
    }

    /**
     * Get the server ip
     *
     * @api
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set the server ip and port
     *
     * @api
     * @param string $ip   Server ip
     * @param int    $port (optional) Server port
     * @return static
     */
    public function setIp($ip, $port = null)
    {
        $this->login = null;
        $this->ip    = (string)$ip;
        if ($port) {
            $this->setPort($port);
        }
        return $this;
    }

    /**
     * Get the server port
     *
     * @api
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set the server port
     *
     * @param int $port Server port
     * @return static
     */
    public function setPort($port)
    {
        $this->port = (int)$port;
        return $this;
    }

    /**
     * @see Element::render()
     */
    public function render(\DOMDocument $domDocument)
    {
        $domElement = $domDocument->createElement("add_favourite");

        if ($this->login) {
            $loginElement = $domDocument->createElement("login", $this->login);
            $domElement->appendChild($loginElement);
        } else {
            $ipElement = $domDocument->createElement("ip", $this->ip . ":" . $this->port);
            $domElement->appendChild($ipElement);
        }

        return $domElement;
    }

}
