<?php

namespace FML\Types;

/**
 * Interface for identifiable Elements
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Identifiable
{

    /**
     * Get the Id
     *
     * @api
     * @return string
     */
    public function getId();

    /**
     * Set the Id
     *
     * @api
     * @param string $id ID
     * @return static
     */
    public function setId($id);

    /**
     * Check and return the Id
     *
     * @return string
     */
    public function checkId();

}
