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
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\System;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventToolBundle\Config\EventExecutionState;
use Markocupic\SacEventToolBundle\Config\EventMountainGuide;
use Markocupic\SacEventToolBundle\Config\EventState;
use Markocupic\SacEventToolBundle\Config\EventType;
use Markocupic\SacPilatusEventStats\Stats\_01AdvertisedEvents;
use Markocupic\SacPilatusEventStats\Stats\_02TourGuides;
use Markocupic\SacPilatusEventStats\Stats\_03EventSubscriptions;
use Markocupic\SacPilatusEventStats\Stats\_04EventStatesAndExecutionStates;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/%contao.backend.route_prefix%/sac_pilatus_event_stats', name: self::class, defaults: ['_scope' => 'backend'])]
class EventStatsController extends AbstractBackendController
{
    public const BACKEND_MODULE_TYPE = 'sac_pilatus_event_stats';
    public const BACKEND_MODULE_CATEGORY = 'sac_be_modules';

    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly _01AdvertisedEvents $_01AdvertisedEvents,
        private readonly _02TourGuides $_02TourGuides,
        private readonly _03EventSubscriptions $_03EventSubscriptions,
        private readonly _04EventStatesAndExecutionStates $_04EventStatesAndExecutionStates,
    ) {
    }

    /**
     * @throws Exception
     */
    public function __invoke(): Response
    {
        $this->checkPermission();

        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile('modules');

        $currentYear = (int) date('Y', time());

        // Create statistics for these time periods.
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
                'headline' => $this->translator->trans('MOD.'.self::BACKEND_MODULE_TYPE.'.0', [], 'contao_default'),
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

                /*
                 * 04 event state cases
                 *
                 * Events stattgefunden [nur mit Tourrapport: executionState != "" AND eventState = ""]
                 * SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.executionState != "") AND (t.eventState = "") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_0' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, 't.executionState != ""', 't.eventState = ""', null),

                /*
                 *  Events ausgebucht [eventState = "Event ausgebucht"]
                 *  SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.eventState = "event_fully_booked") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_1' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, null, 't.eventState = "'.EventState::STATE_FULLY_BOOKED.'"', null),

                /*
                 *  Events verschoben [eventState = "Event verschoben"]
                 *  SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.eventState = "event_rescheduled") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_2' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, null, 't.eventState = "'.EventState::STATE_RESCHEDULED.'"', null),

                /*
                 *  Events abgesagt [eventState = "Event abgesagt"]
                 *  SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.eventState = "event_canceled") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_3' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, null, 't.eventState = "'.EventState::STATE_CANCELED.'"', null),

                /*
                 *  Unbekannt (kein Tourrapport vorhanden) [else-Fall : executionState = "" AND eventState = ""]
                 *  SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.executionState != "") AND (t.eventState = "") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_4' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, 't.executionState = ""', 't.eventState = ""', null),

                /*
                 * Event wie ausgeschrieben durchgeführt: Ja [t.executionState = "event_executed_like_predicted"]
                 * SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.executionState = "event_executed_like_predicted") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_5' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, 't.executionState = "'.EventExecutionState::STATE_EXECUTED_LIKE_PREDICTED.'"', null, null),

                /*
                 * Event wie ausgeschrieben durchgeführt: Nein [t.executionState = "event_not_executed_like_predicted"]
                 * SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.executionState = "event_not_executed_like_predicted") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_6' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, 't.executionState = "'.EventExecutionState::STATE_NOT_EXECUTED_LIKE_PREDICTED.'"', null, null),

                /*
                 * Event wie ausgeschrieben durchgeführt: Unbekannt (kein Tourrapport vorhanden) [t.executionState = ""]
                 * SELECT COUNT(id) FROM tl_calendar_events t WHERE (t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd) AND (t.eventReleaseLevel IN (6, 7)) AND (t.executionState = "") AND (t.eventType = :eventType)
                 */
                '_04_event_state__case_7' => $this->_04EventStatesAndExecutionStates->countEventsByExecutionStateAndEventState($timePeriods, $arrAcceptedReleaseLevels, EventType::TOUR, 't.executionState = ""', null, 0),
            ]
        );
    }

    private function checkPermission(): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        if ($this->security->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'sac_pilatus_event_stats')) {
            return;
        }

        throw new AccessDeniedException('Access denied');
    }
}
