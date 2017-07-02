<?php

namespace FML\XmlRpc;

/**
 * Class representing TrackMania UI Properties
 *
 * @author    steeffeen
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class TMUIProperties extends UIProperties
{

    /**
     * @var array $liveInfoProperties Live info properties
     */
    protected $liveInfoProperties = array();

    /**
     * @var array $spectatorInfoProperties Spectator info properties
     */
    protected $spectatorInfoProperties = array();

    /**
     * @var array $opponentsInfoProperties Opponents info properties
     */
    protected $opponentsInfoProperties = array();

    /**
     * @var array $checkpointListProperties Checkpoint list properties
     */
    protected $checkpointListProperties = array();

    /**
     * @var array $roundScoresProperties Round scores properties
     */
    protected $roundScoresProperties = array();

    /**
     * @var array $chronoProperties Chrono properties
     */
    protected $chronoProperties = array();

    /**
     * @var array $speedAndDistanceProperties Speed and distance properties
     */
    protected $speedAndDistanceProperties = array();

    /**
     * @var array $personalBestAndRankProperties Personal best and rank properties
     */
    protected $personalBestAndRankProperties = array();

    /**
     * @var array $positionProperties Position properties
     */
    protected $positionProperties = array();

    /**
     * @var array $checkpointTimeProperties Checkpoint time properties
     */
    protected $checkpointTimeProperties = array();

    /**
     * @var array $warmUpProperties Warm-up properties
     */
    protected $warmUpProperties = array();

    /**
     * @var array $multiLapInfoProperties Multi-lap info properties
     */
    protected $multiLapInfoProperties = array();

    /**
     * @var array $checkpointRankingProperties Checkpoint ranking properties
     */
    protected $checkpointRankingProperties = array();

    /**
     * Get the live info visibility
     *
     * @api
     * @return bool
     */
    public function getLiveInfoVisible()
    {
        return $this->getVisibleProperty($this->liveInfoProperties);
    }

    /**
     * Set the live info visibility
     *
     * @api
     * @param bool $visible If the live info should be visible
     * @return static
     */
    public function setLiveInfoVisible($visible)
    {
        $this->setVisibleProperty($this->liveInfoProperties, $visible);
        return $this;
    }

    /**
     * Get the live info position
     *
     * @api
     * @return string
     */
    public function getLiveInfoPosition()
    {
        return $this->getPositionProperty($this->liveInfoProperties);
    }

    /**
     * Set the live info position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setLiveInfoPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->liveInfoProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the spectator info visibility
     *
     * @api
     * @return bool
     */
    public function getSpectatorInfoVisible()
    {
        return $this->getVisibleProperty($this->spectatorInfoProperties);
    }

    /**
     * Set the spectator info visibility
     *
     * @api
     * @param bool $visible If the spectator info should be visible
     * @return static
     */
    public function setSpectatorInfoVisible($visible)
    {
        $this->setVisibleProperty($this->spectatorInfoProperties, $visible);
        return $this;
    }

    /**
     * Get the spectator info position
     *
     * @api
     * @return string
     */
    public function getSpectatorInfoPosition()
    {
        return $this->getPositionProperty($this->spectatorInfoProperties);
    }

    /**
     * Set the spectator info position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setSpectatorInfoPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->spectatorInfoProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the opponents info visibility
     *
     * @api
     * @return bool
     */
    public function getOpponentsInfoVisible()
    {
        return $this->getVisibleProperty($this->opponentsInfoProperties);
    }

    /**
     * Set the opponents info visibility
     *
     * @api
     * @param bool $visible If the opponents info should be visible
     * @return static
     */
    public function setOpponentsInfoVisible($visible)
    {
        $this->setVisibleProperty($this->opponentsInfoProperties, $visible);
        return $this;
    }

    /**
     * Get the checkpoint list visibility
     *
     * @api
     * @return bool
     */
    public function getCheckpointListVisible()
    {
        return $this->getVisibleProperty($this->checkpointListProperties);
    }

    /**
     * Set the checkpoint list visibility
     *
     * @api
     * @param bool $visible If the checkpoint list should be visible
     * @return static
     */
    public function setCheckpointListVisible($visible)
    {
        $this->setVisibleProperty($this->checkpointListProperties, $visible);
        return $this;
    }

    /**
     * Get the checkpoint list position
     *
     * @api
     * @return string
     */
    public function getCheckpointListPosition()
    {
        return $this->getPositionProperty($this->checkpointListProperties);
    }

    /**
     * Set the checkpoint list position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setCheckpointListPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->checkpointListProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the round scores visibility
     *
     * @api
     * @return bool
     */
    public function getRoundScoresVisible()
    {
        return $this->getVisibleProperty($this->roundScoresProperties);
    }

    /**
     * Set the round scores visibility
     *
     * @api
     * @param bool $visible If the round scores should be visible
     * @return static
     */
    public function setRoundScoresVisible($visible)
    {
        $this->setVisibleProperty($this->roundScoresProperties, $visible);
        return $this;
    }

    /**
     * Get the round scores position
     *
     * @api
     * @return string
     */
    public function getRoundScoresPosition()
    {
        return $this->getPositionProperty($this->roundScoresProperties);
    }

    /**
     * Set the round scores position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setRoundScoresPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->roundScoresProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the chrono visibility
     *
     * @api
     * @return bool
     */
    public function getChronoVisible()
    {
        return $this->getVisibleProperty($this->chronoProperties);
    }

    /**
     * Set the chrono visibility
     *
     * @api
     * @param bool $visible If the chrono should be visible
     * @return static
     */
    public function setChronoVisible($visible)
    {
        $this->setVisibleProperty($this->chronoProperties, $visible);
        return $this;
    }

    /**
     * Get the chrono position
     *
     * @api
     * @return string
     */
    public function getChronoPosition()
    {
        return $this->getPositionProperty($this->chronoProperties);
    }

    /**
     * Set the chrono position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setChronoPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->chronoProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the speed and distance visibility
     *
     * @api
     * @return bool
     */
    public function getSpeedAndDistanceVisible()
    {
        return $this->getVisibleProperty($this->speedAndDistanceProperties);
    }

    /**
     * Set the speed and distance visibility
     *
     * @api
     * @param bool $visible If the speed and distance should be visible
     * @return static
     */
    public function setSpeedAndDistanceVisible($visible)
    {
        $this->setVisibleProperty($this->speedAndDistanceProperties, $visible);
        return $this;
    }

    /**
     * Get the speed and distance position
     *
     * @api
     * @return string
     */
    public function getSpeedAndDistancePosition()
    {
        return $this->getPositionProperty($this->speedAndDistanceProperties);
    }

    /**
     * Set the speed and distance position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setSpeedAndDistancePosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->speedAndDistanceProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the personal best and rank visibility
     *
     * @api
     * @return bool
     */
    public function getPersonalBestAndRankVisible()
    {
        return $this->getVisibleProperty($this->personalBestAndRankProperties);
    }

    /**
     * Set the personal best and rank visibility
     *
     * @api
     * @param bool $visible If the personal best and rank should be visible
     * @return static
     */
    public function setPersonalBestAndRankVisible($visible)
    {
        $this->setVisibleProperty($this->personalBestAndRankProperties, $visible);
        return $this;
    }

    /**
     * Get the personal best and rank position
     *
     * @api
     * @return string
     */
    public function getPersonalBestAndRankPosition()
    {
        return $this->getPositionProperty($this->personalBestAndRankProperties);
    }

    /**
     * Set the personal best and rank position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setPersonalBestAndRankPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->personalBestAndRankProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the position visibility
     *
     * @api
     * @return bool
     */
    public function getPositionVisible()
    {
        return $this->getVisibleProperty($this->positionProperties);
    }

    /**
     * Set the position visibility
     *
     * @api
     * @param bool $visible If the position should be visible
     * @return static
     */
    public function setPositionVisible($visible)
    {
        $this->setVisibleProperty($this->positionProperties, $visible);
        return $this;
    }

    /**
     * Get the position position
     *
     * @api
     * @return string
     */
    public function getPositionPosition()
    {
        return $this->getPositionProperty($this->positionProperties);
    }

    /**
     * Set the position position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setPositionPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->positionProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the checkpoint time visibility
     *
     * @api
     * @return bool
     */
    public function getCheckpointTimeVisible()
    {
        return $this->getVisibleProperty($this->checkpointTimeProperties);
    }

    /**
     * Set the checkpoint time visibility
     *
     * @api
     * @param bool $visible If the checkpoint time should be visible
     * @return static
     */
    public function setCheckpointTimeVisible($visible)
    {
        $this->setVisibleProperty($this->checkpointTimeProperties, $visible);
        return $this;
    }

    /**
     * Get the checkpoint time position
     *
     * @api
     * @return string
     */
    public function getCheckpointTimePosition()
    {
        return $this->getPositionProperty($this->checkpointTimeProperties);
    }

    /**
     * Set the checkpoint time position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setCheckpointTimePosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->checkpointTimeProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the warm-up visibility
     *
     * @api
     * @return bool
     */
    public function getWarmUpVisible()
    {
        return $this->getVisibleProperty($this->warmUpProperties);
    }

    /**
     * Set the warm-up visibility
     *
     * @api
     * @param bool $visible If the warm-up should be visible
     * @return static
     */
    public function setWarmUpVisible($visible)
    {
        $this->setVisibleProperty($this->warmUpProperties, $visible);
        return $this;
    }

    /**
     * Get the warm-up position
     *
     * @api
     * @return string
     */
    public function getWarmUpPosition()
    {
        return $this->getPositionProperty($this->warmUpProperties);
    }

    /**
     * Set the warm-up position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setWarmUpPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->warmUpProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the multi-lap info visibility
     *
     * @api
     * @return bool
     */
    public function getMultiLapInfoVisible()
    {
        return $this->getVisibleProperty($this->multiLapInfoProperties);
    }

    /**
     * Set the multi-lap info visibility
     *
     * @api
     * @param bool $visible If the multi-lap info should be visible
     * @return static
     */
    public function setMultiLapInfoVisible($visible)
    {
        $this->setVisibleProperty($this->multiLapInfoProperties, $visible);
        return $this;
    }

    /**
     * Get the multi-lap info position
     *
     * @api
     * @return string
     */
    public function getMultiLapInfoPosition()
    {
        return $this->getPositionProperty($this->multiLapInfoProperties);
    }

    /**
     * Set the multi-lap info position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setMultiLapInfoPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->multiLapInfoProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * Get the checkpoint ranking visibility
     *
     * @api
     * @return bool
     */
    public function getCheckpointRankingVisible()
    {
        return $this->getVisibleProperty($this->checkpointRankingProperties);
    }

    /**
     * Set the checkpoint ranking visibility
     *
     * @api
     * @param bool $visible If the checkpoint ranking should be visible
     * @return static
     */
    public function setCheckpointRankingVisible($visible)
    {
        $this->setVisibleProperty($this->checkpointRankingProperties, $visible);
        return $this;
    }

    /**
     * Get the checkpoint ranking position
     *
     * @api
     * @return string
     */
    public function getCheckpointRankingPosition()
    {
        return $this->getPositionProperty($this->checkpointRankingProperties);
    }

    /**
     * Set the checkpoint ranking position
     *
     * @api
     * @param float $positionX X position
     * @param float $positionY Y position
     * @param float $positionZ (optional) Z position (Z-index)
     * @return static
     */
    public function setCheckpointRankingPosition($positionX, $positionY, $positionZ = null)
    {
        $this->setPositionProperty($this->checkpointRankingProperties, $positionX, $positionY, $positionZ);
        return $this;
    }

    /**
     * @see UIProperties::getProperties()
     */
    protected function getProperties()
    {
        return array_merge(parent::getProperties(), array(
            "live_info" => $this->liveInfoProperties,
            "spectator_info" => $this->spectatorInfoProperties,
            "opponents_info" => $this->opponentsInfoProperties,
            "checkpoint_list" => $this->checkpointListProperties,
            "round_scores" => $this->roundScoresProperties,
            "chrono" => $this->chronoProperties,
            "speed_and_distance" => $this->speedAndDistanceProperties,
            "personal_best_and_rank" => $this->personalBestAndRankProperties,
            "position" => $this->positionProperties,
            "checkpoint_time" => $this->checkpointTimeProperties,
            "warmup" => $this->warmUpProperties,
            "multilap_info" => $this->multiLapInfoProperties,
            "checkpoint_ranking" => $this->checkpointRankingProperties
        ));
    }

}
