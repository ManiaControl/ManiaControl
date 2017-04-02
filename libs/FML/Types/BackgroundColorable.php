<?php

namespace FML\Types;

/**
 * Interface for Elements with background color attribute
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface BackgroundColorable
{

    /**
     * Get the background color
     *
     * @api
     * @return string
     */
    public function getBackgroundColor();

    /**
     * Set the background color
     *
     * @api
     * @param string $backgroundColor Background color
     * @return static
     */
    public function setBackgroundColor($backgroundColor);

    /**
     * Get the focus background color
     *
     * @api
     * @return string
     */
    public function getFocusBackgroundColor();

    /**
     * Set the focus background color
     *
     * @api
     * @param string $focusBackgroundColor Focus background color
     * @return static
     */
    public function setFocusBackgroundColor($focusBackgroundColor);

}
