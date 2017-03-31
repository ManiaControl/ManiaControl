<?php

namespace FML\Types;

use FML\Elements\Format;

/**
 * Interface for Element being able to contain other Controls
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Container
{

    /**
     * Get the children
     *
     * @api
     * @return Renderable[]
     */
    public function getChildren();

    /**
     * Add a new child
     *
     * @api
     * @param Renderable $child Child Control to add
     * @return static
     */
    public function addChild(Renderable $child);

    /**
     * Add new children
     *
     * @api
     * @param Renderable[] $children Child Controls to add
     * @return static
     */
    public function addChildren(array $children);

    /**
     * Remove all children
     *
     * @api
     * @return static
     */
    public function removeAllChildren();

    /**
     * Get the Format
     *
     * @api
     * @return Format
     */
    public function getFormat();

    /**
     * Set the Format
     *
     * @api
     * @param Format $format New Format
     * @return static
     */
    public function setFormat(Format $format = null);

}
