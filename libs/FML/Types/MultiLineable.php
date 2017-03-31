<?php

namespace FML\Types;

/**
 * Interface for Elements with multi line features
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface MultiLineable
{

    /**
     * Get auto new line
     *
     * @api
     * @return bool
     */
    public function getAutoNewLine();

    /**
     * Set auto new line
     *
     * @api
     * @param bool $autoNewLine If the Element should insert new lines automatically
     * @return static
     */
    public function setAutoNewLine($autoNewLine);

    /**
     * Get line spacing
     *
     * @api
     * @return float
     */
    public function getLineSpacing();

    /**
     * Set line spacing
     *
     * @api
     * @param float $lineSpacing Line spacing
     * @return static
     */
    public function setLineSpacing($lineSpacing);

    /**
     * Get the maximum number of lines
     *
     * @api
     * @return int
     */
    public function getMaxLines();

    /**
     * Set the maximum number of lines
     *
     * @api
     * @param int $maxLines Maximum number of lines
     * @return static
     */
    public function setMaxLines($maxLines);

}
