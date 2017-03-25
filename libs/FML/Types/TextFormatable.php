<?php

namespace FML\Types;

/**
 * Interface for Elements with formatable text
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface TextFormatable
{

    /**
     * Get the text size
     *
     * @api
     * @return int
     */
    public function getTextSize();

    /**
     * Set the text size
     *
     * @api
     * @param int $textSize Text size
     * @return static
     */
    public function setTextSize($textSize);

    /**
     * Get the text font
     *
     * @api
     * @return string
     */
    public function getTextFont();

    /**
     * Set the text font
     *
     * @api
     * @param string $textFont
     * @return static
     */
    public function setTextFont($textFont);

    /**
     * Get the text color
     *
     * @api
     * @return string
     */
    public function getTextColor();

    /**
     * Set the text color
     *
     * @api
     * @param string $textColor Text color
     * @return static
     */
    public function setTextColor($textColor);

    /**
     * Get the area color
     *
     * @api
     * @return string
     */
    public function getAreaColor();

    /**
     * Set the area color
     *
     * @api
     * @param string $areaColor Area color
     * @return static
     */
    public function setAreaColor($areaColor);

    /**
     * Get the area focus color
     *
     * @api
     * @return string
     */
    public function getAreaFocusColor();

    /**
     * Set the area focus color
     *
     * @api
     * @param string $areaFocusColor Area focus color
     * @return static
     */
    public function setAreaFocusColor($areaFocusColor);

}
