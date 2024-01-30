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

readonly class _01AdvertisedTours
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param array<TimePeriod> $timePeriods
     *
     * @throws Exception
     *
     * @return array<DataItem>
     */
    public function countTours(array $timePeriods): array
    {
        $data = [];
        $qb = $this->connection->createQueryBuilder();

        foreach ($timePeriods as $timePeriod) {
            $qb->select('COUNT(id)')
                ->from('tl_calendar_events', 't')
                ->where('t.eventType = ?')
                ->andWhere('t.startDate >= ?')
                ->andWhere('t.startDate <= ?')
                ->andWhere('t.published = ?')
                ->setParameters([
                    EventType::TOUR,
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    '1',
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
    public function countToursByMountainGuideType(array $timePeriods, int $mountainGuideType): array
    {
        if (!\in_array($mountainGuideType, EventMountainGuide::ALL, true)) {
            throw new \Exception(sprintf('Invalid parameter "$mountainGuideType". Should be either "%d" or "%d".', EventMountainGuide::WITH_MOUNTAIN_GUIDE, EventMountainGuide::WITH_MOUNTAIN_GUIDE_OFFER));
        }

        $data = [];
        $qb = $this->connection->createQueryBuilder();

        foreach ($timePeriods as $timePeriod) {
            $qb->select('COUNT(id)')
                ->from('tl_calendar_events', 't')
                ->where('t.eventType = ?')
                ->andWhere('t.startDate >= ?')
                ->andWhere('t.startDate <= ?')
                ->andWhere('t.published = ?')
                ->andWhere('t.mountainguide = ?')
                ->setParameters([
                    EventType::TOUR,
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    '1',
                    $mountainGuideType,
                ])
            ;

            $count = $qb->fetchOne();

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }
}
