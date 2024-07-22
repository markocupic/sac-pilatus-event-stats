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
use Doctrine\DBAL\Types\Types;
use Markocupic\SacPilatusEventStats\Data\DataItem;
use Markocupic\SacPilatusEventStats\Util\EventReleaseLevelUtil;

readonly class _04EventStatesAndExecutionStates
{
    public function __construct(
        private Connection $connection,
        private EventReleaseLevelUtil $eventReleaseLevelUtil,
    ) {
    }

    public function countEventsByExecutionStateAndEventState(array $timePeriods, array $arrAcceptedReleaseLevels, string|null $eventType = null, string|null $strEventExecutionStateFilter = null, string|null $strEventStateFilter = null, int|null $organizerId = null): array
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
                ->where('t.startDate >= :dateLimitStart AND t.startDate <= :dateLimitEnd')
                ->setParameter('dateLimitStart', $timePeriod->getStartTime(), Types::INTEGER)
                ->setParameter('dateLimitEnd', $timePeriod->getEndTime(), Types::INTEGER)
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
            ;

            // execution state filter
            if (\is_string($strEventExecutionStateFilter) && \strlen($strEventExecutionStateFilter)) {
                $qb->andWhere($strEventExecutionStateFilter);
            }

            // event state filter
            if (\is_string($strEventStateFilter) && \strlen($strEventStateFilter)) {
                $qb->andWhere($strEventStateFilter);
            }

            // event type filter
            if (null !== $eventType && \strlen($eventType)) {
                $qb->andWhere('t.eventType = :eventType');
                $qb->setParameter('eventType', $eventType, Types::STRING);
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
}
