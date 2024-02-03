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
use Markocupic\SacEventToolBundle\Config\EventType;
use Markocupic\SacPilatusEventStats\Stats\_01AdvertisedEvents;
use Markocupic\SacPilatusEventStats\Stats\_02TourGuides;
use Markocupic\SacPilatusEventStats\Stats\_03EventSubscriptions;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/%contao.backend.route_prefix%/event_stats', name: self::class, defaults: ['_scope' => 'backend'])]
class EventStatsController extends AbstractBackendController
{
    public function __construct(
        private readonly _01AdvertisedEvents $_01AdvertisedEvents,
        private readonly _02TourGuides $_02TourGuides,
        private readonly _03EventSubscriptions $_03EventSubscriptions,
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
                // 01 advertised tours
                '_01_advertised_tours__total' => $this->_01AdvertisedEvents->countEvents($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR),
                '_01_advertised_tours__with_mountain_guide' => $this->_01AdvertisedEvents->countEventsByMountainGuideType($timePeriods, EventMountainGuide::WITH_MOUNTAIN_GUIDE, $arrAcceptedReleaseLevels, EventType::TOUR),
                '_01_advertised_tours__with_mountain_guide_offer' => $this->_01AdvertisedEvents->countEventsByMountainGuideType($timePeriods, EventMountainGuide::WITH_MOUNTAIN_GUIDE_OFFER, $arrAcceptedReleaseLevels, EventType::TOUR),
                '_01_advertised_tours__without_mountain_guide' => $this->_01AdvertisedEvents->countEventsByMountainGuideType($timePeriods, EventMountainGuide::NO_MOUNTAIN_GUIDE, $arrAcceptedReleaseLevels, EventType::TOUR),

                // 01 advertised workshops
                '_01_advertised_workshops__total' => $this->_01AdvertisedEvents->countEvents($timePeriods, $arrAcceptedReleaseLevels, EventType::COURSE),
                '_01_advertised_workshops__with_mountain_guide' => $this->_01AdvertisedEvents->countEventsByMountainGuideType($timePeriods, EventMountainGuide::WITH_MOUNTAIN_GUIDE, $arrAcceptedReleaseLevels, EventType::COURSE),
                '_01_advertised_workshops__with_mountain_guide_offer' => $this->_01AdvertisedEvents->countEventsByMountainGuideType($timePeriods, EventMountainGuide::WITH_MOUNTAIN_GUIDE_OFFER, $arrAcceptedReleaseLevels, EventType::COURSE),
                '_01_advertised_workshops__without_mountain_guide' => $this->_01AdvertisedEvents->countEventsByMountainGuideType($timePeriods, EventMountainGuide::NO_MOUNTAIN_GUIDE, $arrAcceptedReleaseLevels, EventType::COURSE),

                // 01 advertised events
                '_01_advertised_workshops__organizers' => $this->_01AdvertisedEvents->getOrganizers(),
                '_01_advertised_workshops__events_all' => $this->_01AdvertisedEvents->countEvents($timePeriods, $arrAcceptedReleaseLevels),
                '_01_advertised_workshops__events_grouped_by_organizer' => $this->_01AdvertisedEvents->countEventsGroupedByOrganizer($timePeriods, $arrAcceptedReleaseLevels),

                // 02 count tour guides
                '_02_tour_guides__all' => $this->_02TourGuides->countTourGuides($timePeriods, $arrAcceptedReleaseLevels),
                '_02_tour_guides__not_mountain_guide' => $this->_02TourGuides->countTourGuides($timePeriods, $arrAcceptedReleaseLevels, false),
                '_02_tour_guides__mountain_guide' => $this->_02TourGuides->countTourGuides($timePeriods, $arrAcceptedReleaseLevels, true),

                // 03 event subscriptions
                '_03_event_subscriptions__total' => $this->_03EventSubscriptions->countEventSubscriptionsTotal($timePeriods, $arrAcceptedReleaseLevels),
                '_03_event_subscriptions__grouped' => $this->_03EventSubscriptions->countEventSubscriptionsGrouped($timePeriods, $arrAcceptedReleaseLevels),

                // 03 event subscription gender and age distribution
                '_03_event_subscriptions__age_and_gender_distribution' => $this->_03EventSubscriptions->getEventSubscriptionsAgeAndGenderDistribution($timePeriods, [4]),
            ]
        );
    }
}
