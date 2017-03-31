<?php

namespace FML\Controls\Quads;

use FML\Controls\Quad;

/**
 * Quad class for 'BgsChallengeMedals' styles
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Quad_BgsChallengeMedals extends Quad
{

    /*
     * Constants
     */
    const STYLE                = 'BgsChallengeMedals';
    const SUBSTYLE_BgBronze    = 'BgBronze';
    const SUBSTYLE_BgGold      = 'BgGold';
    const SUBSTYLE_BgNadeo     = 'BgNadeo';
    const SUBSTYLE_BgNotPlayed = 'BgNotPlayed';
    const SUBSTYLE_BgPlayed    = 'BgPlayed';
    const SUBSTYLE_BgSilver    = 'BgSilver';

    /**
     * @var string $style Style
     */
    protected $style = self::STYLE;

}
