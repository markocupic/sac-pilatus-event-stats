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
use Markocupic\SacEventToolBundle\Config\EventSubscriptionState;
use Markocupic\SacEventToolBundle\Config\EventType;
use Markocupic\SacPilatusEventStats\Data\DataItem;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;
use Markocupic\SacPilatusEventStats\Util\EventReleaseLevelUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class _03EventSubscriptions
{
    public function __construct(
        private Connection $connection,
        private EventReleaseLevelUtil $eventReleaseLevelUtil,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<TimePeriod> $timePeriods
     * @param array<int>        $arrAcceptedReleaseLevels
     *
     * @throws Exception
     *
     * @return array<array>
     */
    public function getEventSubscriptionsAgeAndGenderDistribution(array $timePeriods, array $arrAcceptedReleaseLevels): array
    {
        $data = [
            'total' => [],
            'gender_female' => [],
            'gender_male' => [],
            'gender_divers' => [],
            'age_0-20' => [],
            'age_21-30' => [],
            'age_31-40' => [],
            'age_41-60' => [],
            'age_61-80' => [],
            'age_81+' => [],
            'age_undefined' => [],
        ];

        // Prepare the data array
        foreach ($timePeriods as $timePeriod) {
            foreach (array_keys($data) as $key) {
                $data[$key]['label'] = $this->translator->trans('SAC_PILATUS_EVENT_STATS.'.$key, [], 'contao_default');
                $data[$key][(int) $timePeriod->getFormattedStartTime('Y')] = 0;
            }
        }

        $arrAcceptedReleaseLevelIds = $this->eventReleaseLevelUtil->getAllowedEventReleaseLevelIds($arrAcceptedReleaseLevels);

        if (empty($arrAcceptedReleaseLevelIds)) {
            return $data;
        }

        foreach ($timePeriods as $timePeriod) {
            $qbSub = $this->connection->createQueryBuilder();

            $arrEventIds = $qbSub->select('tt.id')
                ->from('tl_calendar_events', 'tt')
                ->where('tt.startDate >= ? AND tt.startDate <= ?')
                ->andWhere('tt.eventType = ? OR tt.eventType = ?')
                ->andWhere($qbSub->expr()->in('tt.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::TOUR,
                    EventType::COURSE,
                ])
                ->fetchFirstColumn()
                ;

            $qb = $this->connection->createQueryBuilder();

            $arrSubscriptions = $qb->select('t.dateOfBirth,t.gender')
                ->from('tl_calendar_events_member', 't')
                ->where('t.hasParticipated = ?')
                ->andWhere($qb->expr()->in('t.eventId', $arrEventIds))
                ->setParameters([
                    '1',
                ])
                ->fetchAllAssociative()
            ;

            $year = (int) $timePeriod->getFormattedStartTime('Y');

            foreach ($arrSubscriptions as $row) {
                $gender = $row['gender'];
                $dateOfBirth = $row['dateOfBirth'];

                // total
                ++$data['total'][$year];

                // gender distribution
                if ('female' === $gender) {
                    ++$data['gender_female'][$year];
                } elseif ('male' === $gender) {
                    ++$data['gender_male'][$year];
                } else {
                    ++$data['gender_divers'][$year];
                }

                // age distribution
                if ('' === $dateOfBirth) {
                    ++$data['age_undefined'][$year];
                }

                $yearOfBirth = (int) date('Y', (int) $dateOfBirth);
                $age = $year - $yearOfBirth;

                if ($age >= 81) {
                    ++$data['age_81+'][$year];
                } elseif ($age >= 61) {
                    ++$data['age_61-80'][$year];
                } elseif ($age >= 41) {
                    ++$data['age_41-60'][$year];
                } elseif ($age >= 31) {
                    ++$data['age_31-40'][$year];
                } elseif ($age >= 21) {
                    ++$data['age_21-30'][$year];
                } else {
                    ++$data['age_0-20'][$year];
                }
            }
        }

        return $data;
    }

    /**
     * @param array<TimePeriod> $timePeriods
     * @param array<int>        $arrAcceptedReleaseLevels
     *
     * @throws Exception
     *
     * @return array<DataItem>
     */
    public function countEventSubscriptionsTotal(array $timePeriods, array $arrAcceptedReleaseLevels): array
    {
        $data = [];

        $qb = $this->connection->createQueryBuilder();

        $arrAcceptedReleaseLevelIds = $this->eventReleaseLevelUtil->getAllowedEventReleaseLevelIds($arrAcceptedReleaseLevels);

        if (empty($arrAcceptedReleaseLevelIds)) {
            return $data;
        }

        foreach ($timePeriods as $timePeriod) {
            $arrEventIds = $qb->select('id')
                ->from('tl_calendar_events', 't')
                ->where('t.startDate >= ? AND t.startDate <= ?')
                ->andWhere('t.eventType = ? OR t.eventType = ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::TOUR,
                    EventType::COURSE,
                ])
                ->fetchFirstColumn()
            ;

            $qb2 = $this->connection->createQueryBuilder();

            $count = $qb2->select('COUNT(id)')
                ->from('tl_calendar_events_member', 't')
                ->where($qb2->expr()->in('t.eventId', $arrEventIds))
                ->fetchOne()
            ;

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }

    /**
     * @param array<TimePeriod> $timePeriods
     * @param array<int>        $arrAcceptedReleaseLevels
     *
     * @throws Exception
     *
     * @return array<DataItem>
     */
    public function countEventSubscriptionsGrouped(array $timePeriods, array $arrAcceptedReleaseLevels): array
    {
        $data = [];

        $qb = $this->connection->createQueryBuilder();

        $arrAcceptedReleaseLevelIds = $this->eventReleaseLevelUtil->getAllowedEventReleaseLevelIds($arrAcceptedReleaseLevels);

        if (empty($arrAcceptedReleaseLevelIds)) {
            return $data;
        }

        $arrEventIdsAll = [];

        // Get the event ids grouped by year first
        foreach ($timePeriods as $timePeriod) {
            $arrEventIdsAll[] = $qb->select('id')
                ->from('tl_calendar_events', 't')
                ->where('t.startDate >= ? AND t.startDate <= ?')
                ->andWhere('t.eventType = ? OR t.eventType = ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::TOUR,
                    EventType::COURSE,
                ])
                ->fetchFirstColumn()
            ;
        }

        // Iterate through each event subscription state
        foreach (EventSubscriptionState::ALL as $eventSubscriptionsState) {
            $dataSub = [];
            $dataSub['eventSubscriptionState'] = $eventSubscriptionsState;
            $dataSub['eventSubscriptionStateTrans'] = $this->translator->trans('MSC.'.$eventSubscriptionsState, [], 'contao_default');

            // iterate through each time period (year) and count subscriptions of a specific state
            $iYear = 0;

            foreach ($arrEventIdsAll as $arrEventIds) {
                $count = $qb->select('COUNT(id)')
                    ->from('tl_calendar_events_member', 't')
                    ->where($qb->expr()->in('t.eventId', $arrEventIds))
                    ->andWhere('t.stateOfSubscription = ?')
                    ->setParameters([
                        $eventSubscriptionsState,
                    ])
                    ->fetchOne()
                ;

                $dataSub['data'][] = new DataItem($timePeriods[$iYear], $count);

                ++$iYear;
            }

            $data[] = $dataSub;
        }

        return $data;
    }
}
