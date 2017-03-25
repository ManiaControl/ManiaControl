<?php

namespace FML\Types;

/**
 * Interface for Elements with style attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Styleable
{

    /**
     * Get the style
     *
     * @api
     * @return string
     */
    public function getStyle();

    /**
     * Set the style
     *
     * @api
     * @param string $style Style name
     * @return static
     */
    public function setStyle($style);

}
