<?php

namespace FML\Controls\Labels;

use FML\Controls\Label;

/**
 * Label class for text styles
 *
 * @author    steeffeen <mail@steeffeen.com>
 * @copyright FancyManiaLinks Copyright © 2017 Steffen Schröder
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
class Label_Text extends Label
{

    /*
     * Constants
     */
    const STYLE_AvatarButtonNormal         = 'AvatarButtonNormal';
    const STYLE_BgMainMenuTitleHeader      = 'BgMainMenuTitleHeader';
    const STYLE_Default                    = 'Default';
    const STYLE_FrameTransitionFromLeft    = 'FrameTransitionFromLeft';
    const STYLE_FrameTransitionsFromRight  = 'FrameTransitionsFromRight';
    const STYLE_ListItemMedal              = 'ListItemMedal';
    const STYLE_Manialink_Body             = 'Manialink_Body';
    const STYLE_ProgressBar                = 'ProgressBar';
    const STYLE_ProgressBarSmall           = 'ProgressBarSmall';
    const STYLE_SliderSmall                = 'SliderSmall';
    const STYLE_SliderVolume               = 'SliderVolume';
    const STYLE_StyleTextScriptEditor      = 'StyleTextScriptEditor';
    const STYLE_StyleValueYellowSmall      = 'StyleValueYellowSmall';
    const STYLE_TextActionMaker            = 'TextActionMaker';
    const STYLE_TextButtonBig              = 'TextButtonBig';
    const STYLE_TextButtonMedium           = 'TextButtonMedium';
    const STYLE_TextButtonNav              = 'TextButtonNav';
    const STYLE_TextButtonNavBack          = 'TextButtonNavBack';
    const STYLE_TextButtonSmall            = 'TextButtonSmall';
    const STYLE_TextCardInfoSmall          = 'TextCardInfoSmall';
    const STYLE_TextCardInfoVerySmall      = 'TextCardInfoVerySmall';
    const STYLE_TextCardMedium             = 'TextCardMedium';
    const STYLE_TextCardMediumWhite        = 'TextCardMediumWhite';
    const STYLE_TextCardRaceRank           = 'TextCardRaceRank';
    const STYLE_TextCardScores2            = 'TextCardScores2';
    const STYLE_TextCardSmall              = 'TextCardSmall';
    const STYLE_TextCardSmallScores2       = 'TextCardSmallScores2';
    const STYLE_TextCardSmallScores2Rank   = 'TextCardSmallScores2Rank';
    const STYLE_TextChallengeNameMedal     = 'TextChallengeNameMedal';
    const STYLE_TextChallengeNameMedalNone = 'TextChallengeNameMedalNone';
    const STYLE_TextChallengeNameMedium    = 'TextChallengeNameMedium';
    const STYLE_TextChallengeNameSmall     = 'TextChallengeNameSmall';
    const STYLE_TextCongratsBig            = 'TextCongratsBig';
    const STYLE_TextCredits                = 'TextCredits';
    const STYLE_TextCreditsTitle           = 'TextCreditsTitle';
    const STYLE_TextEditorArticle          = 'TextEditorArticle';
    const STYLE_TextInfoMedium             = 'TextInfoMedium';
    const STYLE_TextInfoSmall              = 'TextInfoSmall';
    const STYLE_TextPlayerCardName         = 'TextPlayerCardName';
    const STYLE_TextPlayerCardScore        = 'TextPlayerCardScore';
    const STYLE_TextRaceChat               = 'TextRaceChat';
    const STYLE_TextRaceChrono             = 'TextRaceChrono';
    const STYLE_TextRaceChronoError        = 'TextRaceChronoError';
    const STYLE_TextRaceChronoOfficial     = 'TextRaceChronoOfficial';
    const STYLE_TextRaceChronoWarning      = 'TextRaceChronoWarning';
    const STYLE_TextRaceMessage            = 'TextRaceMessage';
    const STYLE_TextRaceMessageBig         = 'TextRaceMessageBig';
    const STYLE_TextRaceStaticSmall        = 'TextRaceStaticSmall';
    const STYLE_TextRaceValueSmall         = 'TextRaceValueSmall';
    const STYLE_TextRankingsBig            = 'TextRankingsBig';
    const STYLE_TextSPScoreBig             = 'TextSPScoreBig';
    const STYLE_TextSPScoreMedium          = 'TextSPScoreMedium';
    const STYLE_TextSPScoreSmall           = 'TextSPScoreSmall';
    const STYLE_TextStaticMedium           = 'TextStaticMedium';
    const STYLE_TextStaticSmall            = 'TextStaticSmall';
    const STYLE_TextStaticVerySmall        = 'TextStaticVerySmall';
    const STYLE_TextSubTitle1              = 'TextSubTitle1';
    const STYLE_TextSubTitle2              = 'TextSubTitle2';
    const STYLE_TextTips                   = 'TextTips';
    const STYLE_TextTitle1                 = 'TextTitle1';
    const STYLE_TextTitle2                 = 'TextTitle2';
    const STYLE_TextTitle2Blink            = 'TextTitle2Blink';
    const STYLE_TextTitle3                 = 'TextTitle3';
    const STYLE_TextTitle3Header           = 'TextTitle3Header';
    const STYLE_TextTitleError             = 'TextTitleError';
    const STYLE_TextToolTipAM              = 'TextToolTipAM';
    const STYLE_TextToolTipAMBig           = 'TextToolTipAMBig';
    const STYLE_TextValueBig               = 'TextValueBig';
    const STYLE_TextValueMedium            = 'TextValueMedium';
    const STYLE_TextValueMediumSm          = 'TextValueMediumSm';
    const STYLE_TextValueSmall             = 'TextValueSmall';
    const STYLE_TextValueSmallSm           = 'TextValueSmallSm';
    const STYLE_TrackerText                = 'TrackerText';
    const STYLE_TrackerTextBig             = 'TrackerTextBig';
    const STYLE_TrackListItem              = 'TrackListItem';
    const STYLE_TrackListLine              = 'TrackListLine';
    const STYLE_UiDriving_BgBottom         = 'UiDriving_BgBottom';
    const STYLE_UiDriving_BgCard           = 'UiDriving_BgCard';
    const STYLE_UiDriving_BgCenter         = 'UiDriving_BgCenter';

}
