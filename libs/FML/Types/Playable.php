<?php

namespace FML\Types;

/**
 * Interface for Elements with media attributes
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
interface Playable
{

    /**
     * Get the data url
     *
     * @api
     * @return string
     */
    public function getData();

    /**
     * Set the data url
     *
     * @api
     * @param string $data Data url
     * @return static
     */
    public function setData($data);

    /**
     * Get the data id to use from Dico
     *
     * @api
     * @return string
     */
    public function getDataId();

    /**
     * Set the data id to use from Dico
     *
     * @api
     * @param string $dataId Data id
     * @return static
     */
    public function setDataId($dataId);

    /**
     * Get play
     *
     * @api
     * @return bool
     */
    public function getPlay();

    /**
     * Set play
     *
     * @api
     * @param bool $play If the Control should start playing automatically
     * @return static
     */
    public function setPlay($play);

    /**
     * Get looping
     *
     * @api
     * @return bool
     */
    public function getLooping();

    /**
     * Set looping
     *
     * @api
     * @param bool $looping If the Control should play looping
     * @return static
     */
    public function setLooping($looping);

    /**
     * Get music
     *
     * @api
     * @return bool
     */
    public function getMusic();

    /**
     * Set music
     *
     * @api
     * @param bool $music If the Control represents background music
     * @return static
     */
    public function setMusic($music);

    /**
     * Get the volume
     *
     * @api
     * @return float
     */
    public function getVolume();

    /**
     * Set the volume
     *
     * @api
     * @param float $volume Media volume
     * @return static
     */
    public function setVolume($volume);

}
