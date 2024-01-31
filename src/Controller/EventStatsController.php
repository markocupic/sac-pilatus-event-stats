<?php

declare(strict_types=1);

/*
 * This file is part of SAC Pilatus Event Statistics.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/sac-pilatus-event-stats
 */

namespace Markocupic\SacPilatusEventStats\Controller;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventToolBundle\Config\EventMountainGuide;
use Markocupic\SacPilatusEventStats\Stats\_01AdvertisedTours;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * https://github.com/jonasmueller1/sac-pilatus-website/issues/103.
 */
#[Route('/%contao.backend.route_prefix%/event_stats', name: self::class, defaults: ['_scope' => 'backend'])]
class EventStatsController extends AbstractBackendController
{
    public function __construct(
        private readonly _01AdvertisedTours $_01advertisedTours,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(): Response
    {
        $currentYear = (int) date('Y', time());

        $timePeriods = [
            new TimePeriod(strtotime(($currentYear - 2).'-01-01 00:00:00'), strtotime(($currentYear - 2).'-12-31 23:59:59')),
            new TimePeriod(strtotime(($currentYear - 1).'-01-01 00:00:00'), strtotime(($currentYear - 1).'-12-31 23:59:59')),
            new TimePeriod(strtotime($currentYear.'-01-01 00:00:00'), strtotime($currentYear.'-12-31 23:59:59')),
        ];

        // FS3 and FS4
        $arrAcceptedReleaseLevels = [3, 4];

        return $this->render(
            '@MarkocupicSacPilatusEventStats/Backend/event_stats.html.twig',
            [
                'headline' => 'SAC Pilatus Event Statistik',
                'time_periods' => $timePeriods,
                // 01
                '_01_advertised_tours__total' => $this->_01advertisedTours->countTours($timePeriods, $arrAcceptedReleaseLevels),
                '_01_advertised_tours__with_mountain_guide' => $this->_01advertisedTours->countToursByMountainGuideType($timePeriods, EventMountainGuide::WITH_MOUNTAIN_GUIDE, $arrAcceptedReleaseLevels),
                '_01_advertised_tours__with_mountain_guide_offer' => $this->_01advertisedTours->countToursByMountainGuideType($timePeriods, EventMountainGuide::WITH_MOUNTAIN_GUIDE_OFFER, $arrAcceptedReleaseLevels),
                '_01_advertised_tours__without_mountain_guide' => $this->_01advertisedTours->countToursByMountainGuideType($timePeriods, EventMountainGuide::NO_MOUNTAIN_GUIDE, $arrAcceptedReleaseLevels),
            ]
        );
    }
}
