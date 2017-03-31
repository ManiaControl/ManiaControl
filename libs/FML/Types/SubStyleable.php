<?php

namespace FML\Types;

/**
 * Interface for Elements with substyle attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface SubStyleable
{

    /**
     * Get the sub style
     *
     * @api
     * @return string
     */
    public function getSubStyle();

    /**
     * Set the sub style
     *
     * @api
     * @param string $subStyle SubStyle name
     * @return static
     */
    public function setSubStyle($subStyle);

    /**
     * Set the style and the sub style
     *
     * @api
     * @param string $style    Style name
     * @param string $subStyle SubStyle name
     * @return static
     */
    public function setStyles($style, $subStyle);

}
