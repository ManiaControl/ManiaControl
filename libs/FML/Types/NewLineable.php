<?php

namespace FML\Types;

/**
 * Interface for Elements with autonewline attribute
 *
 * @deprecated Use MultiLineable
 * @see        MultiLineable
 * @author     steeffeen <mail@steeffeen.com>
 * @copyright  FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license    http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface NewLineable
{

    /**
     * Get auto new line
     *
     * @api
     * @deprecated
     * @return bool
     */
    public function getAutoNewLine();

    /**
     * Set auto new line
     *
     * @api
     * @deprecated
     * @param bool $autoNewLine If the Element should insert new lines automatically
     * @return static
     */
    public function setAutoNewLine($autoNewLine);

}
