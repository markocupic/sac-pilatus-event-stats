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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventToolBundle\Config\EventMountainGuide;
use Markocupic\SacEventToolBundle\Config\EventType;
use Markocupic\SacEventToolBundle\Model\EventReleaseLevelPolicyModel;
use Markocupic\SacPilatusEventStats\Data\DataItem;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;

readonly class _01AdvertisedTours
{
    public function __construct(
        private Connection $connection,
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
    public function countTours(array $timePeriods, array $arrAcceptedReleaseLevels): array
    {
        $data = [];
        $qb = $this->connection->createQueryBuilder();

        $arrAcceptedReleaseLevelIDS = $this->getAllowedEventReleaseLevelIDS(EventType::TOUR, $arrAcceptedReleaseLevels);

        if (empty($arrAcceptedReleaseLevelIDS)) {
            return $data;
        }

        foreach ($timePeriods as $timePeriod) {
            $qb->select('COUNT(id)')
                ->from('tl_calendar_events', 't')
                ->where('t.eventType = ?')
                ->andWhere('t.startDate >= ?')
                ->andWhere('t.startDate <= ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIDS))
                ->setParameters([
                    EventType::TOUR,
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                ])
            ;

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
    public function countToursByMountainGuideType(array $timePeriods, int $mountainGuideType, array $arrAcceptedReleaseLevels): array
    {
        if (!\in_array($mountainGuideType, EventMountainGuide::ALL, true)) {
            throw new \Exception(sprintf('Invalid parameter "$mountainGuideType". Should be either "%d" or "%d".', EventMountainGuide::WITH_MOUNTAIN_GUIDE, EventMountainGuide::WITH_MOUNTAIN_GUIDE_OFFER));
        }

        $data = [];
        $qb = $this->connection->createQueryBuilder();

        $arrAcceptedReleaseLevelIDS = $this->getAllowedEventReleaseLevelIDS(EventType::TOUR, $arrAcceptedReleaseLevels);

        if (empty($arrAcceptedReleaseLevelIDS)) {
            return $data;
        }

        foreach ($timePeriods as $timePeriod) {
            $qb->select('COUNT(id)')
                ->from('tl_calendar_events', 't')
                ->where('t.eventType = ?')
                ->andWhere('t.startDate >= ?')
                ->andWhere('t.startDate <= ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIDS))
                ->andWhere('t.mountainguide = ?')
                ->setParameters([
                    EventType::TOUR,
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    $mountainGuideType,
                ])
            ;

            $count = $qb->fetchOne();

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }

    /**
     * @param array $arrAcceptedReleaseLevels<int>
     *
     * @throws Exception
     *
     * @return array<int>
     */
    private function getAllowedEventReleaseLevelIDS(string $eventType, array $arrAcceptedReleaseLevels): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('eventReleaseLevel')
            ->from('tl_calendar_events', 't')
            ->where('eventType = ?')
            ->groupBy('eventReleaseLevel')
            ->setParameters([$eventType])
        ;

        /** @var array<int> $arrReleaseLevels */
        $arrReleaseLevelsIDS = $qb->fetchFirstColumn();

        $qb->select('pid')
            ->from('tl_event_release_level_policy', 't')
            ->where($qb->expr()->in('t.id', ':arrIds'))
            ->setParameter('arrIds', $arrReleaseLevelsIDS, ArrayParameterType::INTEGER)
            ->groupBy('pid')
        ;

        /** @var array<int> $arrReleaseLevelPackages */
        $arrReleaseLevelPackages = $qb->fetchFirstColumn();

        $arrLevels = [];

        foreach ($arrReleaseLevelPackages as $pid) {
            foreach ($arrAcceptedReleaseLevels as $level) {
                $objEventReleaseLevel = EventReleaseLevelPolicyModel::findOneByPidAndLevel($pid, $level);

                if (null !== $objEventReleaseLevel) {
                    $arrLevels[] = $objEventReleaseLevel->id;
                }
            }
        }

        return $arrLevels;
    }
}
