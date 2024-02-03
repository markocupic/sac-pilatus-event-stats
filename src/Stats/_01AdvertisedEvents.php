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

namespace Markocupic\SacPilatusEventStats\Stats;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventToolBundle\Config\EventMountainGuide;
use Markocupic\SacEventToolBundle\Config\EventType;
use Markocupic\SacPilatusEventStats\Data\DataItem;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;
use Markocupic\SacPilatusEventStats\Util\EventReleaseLevelUtil;

readonly class _01AdvertisedEvents
{
    public function __construct(
        private Connection $connection,
        private EventReleaseLevelUtil $eventReleaseLevelUtil,
    ) {
    }

    /**
     * @param array<TimePeriod> $timePeriods
     * @param array<int>        $arrAcceptedReleaseLevels
     *
     * @throws Exception
     *
     * @return array<DataItem>
     */
    public function countEvents(array $timePeriods, array $arrAcceptedReleaseLevels, string|null $eventType = null, int|null $organizerId = null): array
    {
        $data = [];
        $qb = $this->connection->createQueryBuilder();

        $arrAcceptedReleaseLevelIds = $this->eventReleaseLevelUtil->getAllowedEventReleaseLevelIds($arrAcceptedReleaseLevels, $eventType);

        if (empty($arrAcceptedReleaseLevelIds)) {
            return $data;
        }

        foreach ($timePeriods as $timePeriod) {
            $qb->select('COUNT(id)')
                ->from('tl_calendar_events', 't')
                ->where('t.startDate >= ? AND t.startDate <= ?')
                ->andWhere('t.eventType != ? AND t.eventType != ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::GENERAL_EVENT,
                    EventType::LAST_MINUTE_TOUR,
                ])
            ;

            // event type filter
            if (null !== $eventType && \strlen($eventType)) {
                $qb->andWhere(sprintf('t.eventType = "%s"', $eventType));
            }

            // event organizer filter
            if (null !== $organizerId && $organizerId > 0) {
                $qb->andWhere($qb->expr()->like('t.organizers', $qb->expr()->literal('%:"'.$organizerId.'";%')));
            }

            $count = $qb->fetchOne();

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }

    /**
     * @param array<TimePeriod> $timePeriods
     *
     * @throws Exception
     *
     * @return array<DataItem>
     */
    public function countEventsByMountainGuideType(array $timePeriods, int $mountainGuideType, array $arrAcceptedReleaseLevels, string|null $eventType, int|null $organizerId = null): array
    {
        if (!\in_array($mountainGuideType, EventMountainGuide::ALL, true)) {
            throw new \Exception(sprintf('Invalid parameter "$mountainGuideType". Should be either "%d" or "%d".', EventMountainGuide::WITH_MOUNTAIN_GUIDE, EventMountainGuide::WITH_MOUNTAIN_GUIDE_OFFER));
        }

        $data = [];
        $qb = $this->connection->createQueryBuilder();

        $arrAcceptedReleaseLevelIds = $this->eventReleaseLevelUtil->getAllowedEventReleaseLevelIds($arrAcceptedReleaseLevels, $eventType);

        if (empty($arrAcceptedReleaseLevelIds)) {
            return $data;
        }

        foreach ($timePeriods as $timePeriod) {
            $qb->select('COUNT(id)')
                ->from('tl_calendar_events', 't')
                ->where('t.startDate >= ? AND t.startDate <= ?')
                ->andWhere('t.eventType != ? AND t.eventType != ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->andWhere('t.mountainguide = ?')
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::GENERAL_EVENT,
                    EventType::LAST_MINUTE_TOUR,
                    $mountainGuideType,
                ])
            ;

            // event type filter
            if (null !== $eventType && \strlen($eventType)) {
                $qb->andWhere(sprintf('t.eventType = "%s"', $eventType));
            }

            // event organizer filter
            if (null !== $organizerId && $organizerId > 0) {
                $qb->andWhere($qb->expr()->like('t.organizers', $qb->expr()->literal('%:"'.$organizerId.'";%')));
            }

            $count = $qb->fetchOne();

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }

    /**
     * @param array<TimePeriod> $timePeriods
     *
     * @throws Exception
     */
    public function countEventsGroupedByOrganizer(array $timePeriods, array $arrAcceptedReleaseLevels): array
    {
        $data = [];

        $arrOrganizers = $this->getOrganizers();

        foreach ($arrOrganizers as $arrOrganizer) {
            $dataOrg = [];
            $dataOrg['organizer'] = $arrOrganizer;
            $dataOrg['data'] = $this->countEvents($timePeriods, $arrAcceptedReleaseLevels, null, $arrOrganizer['id']);

            $data[] = $dataOrg;
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    public function getOrganizers(): array
    {
        $qb = $this->connection->createQueryBuilder();

        return $qb->select('*')
            ->from('tl_event_organizer', 't')
            ->orderBy('t.sorting', 'ASC')
            ->fetchAllAssociative()
        ;
    }
}
