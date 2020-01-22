<?php

namespace MCTeam\Common;

use FML\Controls\Frame;
use FML\Controls\Label;
use FML\Controls\Quad;
use FML\Controls\Quads\Quad_Bgs1InRace;
use ManiaControl\ManiaControl;
use ManiaControl\Players\Player;
use ManiaControl\Utils\Formatter;

/**
 * ManiaControl Local Records Plugin
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class RecordWidget {

	private $maniaControl;
	private $lineHeight = 4.;
	private $width      = 40.;

	/**
	 * RecordWidget constructor.
	 *
	 * @param \ManiaControl\ManiaControl $maniaControl
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
	}

	/**
	 * Returns a Frame with one line of the Record
	 *
	 * @param                                   $record
	 * @param \ManiaControl\Players\Player|null $player
	 * @return \FML\Controls\Frame
	 */
	public function generateRecordLineFrame($record, Player $player = null) {
		$width           = $this->width;
		$lineHeight      = $this->lineHeight;
		$largeNumberDiff = 0;

		$recordFrame = new Frame();

		if ($record->rank > 999) {
			$largeNumberDiff = 0.03;
		}
        if ($record->rank > 9999) {
            $largeNumberDiff = 0.06;
        }
        if ($record->rank > 99999) {
            $largeNumberDiff = 0.09;
        }
		$rankLabel = new Label();
		$recordFrame->addChild($rankLabel);
		$rankLabel->setHorizontalAlign($rankLabel::LEFT);
		$rankLabel->setX($width * -0.49);
		$rankLabel->setSize($width * (0.09 + $largeNumberDiff), $lineHeight);
		$rankLabel->setTextSize(1);
		$rankLabel->setTextPrefix('$o');
		$rankLabel->setText($record->rank);
		$rankLabel->setTextEmboss(true);

		$nameLabel = new Label();
		$recordFrame->addChild($nameLabel);
		$nameLabel->setHorizontalAlign($nameLabel::LEFT);
		$nameLabel->setX($width * (-0.39 + $largeNumberDiff));
		$nameLabel->setSize($width * (0.6 - $largeNumberDiff), $lineHeight);
		$nameLabel->setTextSize(1);
		$nameLabel->setText($record->nickname);
		$nameLabel->setTextEmboss(true);

		$timeLabel = new Label();
		$recordFrame->addChild($timeLabel);
		$timeLabel->setHorizontalAlign($timeLabel::RIGHT);
		$timeLabel->setX($width * 0.49);
		$timeLabel->setSize($width * 0.27, $lineHeight);
		$timeLabel->setTextSize(1);
		if (isset($record->time)) {
			$timeLabel->setText(Formatter::formatTime($record->time));
		} else {
			$timeLabel->setText(Formatter::formatTime($record->best));
		}
		$timeLabel->setTextEmboss(true);

		if ($player && $player->index == $record->playerIndex) {
			$quad = new Quad();
			$recordFrame->addChild($quad);
			$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
			$quad->setSize($width, $lineHeight);
		}

		return $recordFrame;
	}

	/**
	 * Returns a Frame with Records to a given limit
	 *
	 * @param                                   $records
	 * @param                                   $limit
	 * @param \ManiaControl\Players\Player|null $player
	 * @return \FML\Controls\Frame
	 */
	public function generateRecordsFrame($records, $limit, Player $player = null) {
		$lineHeight = $this->lineHeight;

		$frame = new Frame();

		foreach ($records as $index => $record) {
			if ($index >= $limit) {
				break;
			}

			$y = -8. - $index * $lineHeight;

			$recordFrame = $this->generateRecordLineFrame($record, $player);
			$frame->addChild($recordFrame);
			$recordFrame->setPosition(0, $y);

		}

		return $frame;
	}

	/**
	 * Returns the default separator Quad for the RecordWidget
	 *
	 * @param $width
	 * @return \FML\Controls\Quad
	 */
	public function getLineSeparatorQuad($width) {
		$quad = new Quad();
		$quad->setStyles(Quad_Bgs1InRace::STYLE, Quad_Bgs1InRace::SUBSTYLE_BgCardList);
		$quad->setSize($width, 0.4);

		return $quad;
	}

	/**
	 * @return float
	 */
	public function getLineHeight() {
		return $this->lineHeight;
	}

	/**
	 * @param float $lineHeight
	 */
	public function setLineHeight($lineHeight) {
		$this->lineHeight = $lineHeight;
	}

	/**
	 * @return float
	 */
	public function getWidth() {
		return $this->width;
	}

	/**
	 * @param float $width
	 */
	public function setWidth($width) {
		$this->width = $width;
	}


}