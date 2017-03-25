<?php

namespace FML\Types;

/**
 * Interface for Elements with image attribute
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Imageable
{

    /**
     * Get the image url
     *
     * @api
     * @return string
     */
    public function getImageUrl();

    /**
     * Set the image url
     *
     * @api
     * @param string $imageUrl Image url
     * @return static
     */
    public function setImageUrl($imageUrl);

}
