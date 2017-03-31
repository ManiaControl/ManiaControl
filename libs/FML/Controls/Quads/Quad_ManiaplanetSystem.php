<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'ManiaplanetSystem' styles
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_ManiaplanetSystem extends Quad
{

    /*
     * Constants
     */
    const STYLE                   = 'ManiaplanetSystem';
    const SUBSTYLE_BgDialog       = 'BgDialog';
    const SUBSTYLE_BgDialogAnchor = 'BgDialogAnchor';
    const SUBSTYLE_BgFloat        = 'BgFloat';
    const SUBSTYLE_Events         = 'Events';
    const SUBSTYLE_Medals         = 'Medals';
    const SUBSTYLE_Statistics     = 'Statistics';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
