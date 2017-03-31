<?php

namespace FML\ManiaCode;

/**
 * Base ManiaCode Element
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Element
{

    /**
     * Render the ManiaCode Element
     *
     * @param \DOMDocument $domDocument The DOMDocument for which the Element should be rendered
     * @return \DOMElement
     */
    public function render(\DOMDocument $domDocument);

}
