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
    public function getTotal(array $timePeriods): array
    {
        $data = [];

        foreach ($timePeriods as $timePeriod) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id FROM tl_calendar_events WHERE startDate >= ? AND startTime <= ? AND published = ?',
                [
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    '1',
                ],
            );

            $count = 0;

            if ($rows) {
                $count = \count($rows);
            }

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
    public function getWithMountainGuide(array $timePeriods): array
    {
        $data = [];

        foreach ($timePeriods as $timePeriod) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id FROM tl_calendar_events WHERE startDate >= ? AND startTime <= ? AND published = ? AND mountainguide != ?',
                [
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    '1',
                    EventMountainGuide::NO_MOUNTAIN_GUIDE,
                ],
            );

            $count = 0;

            if ($rows) {
                $count = \count($rows);
            }

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
    public function getWithoutMountainGuide(array $timePeriods): array
    {
        $data = [];

        foreach ($timePeriods as $timePeriod) {
            $rows = $this->connection->fetchAllAssociative(
                'SELECT id FROM tl_calendar_events WHERE startDate >= ? AND startTime <= ? AND published = ? AND mountainguide = ?',
                [
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    '1',
                    EventMountainGuide::NO_MOUNTAIN_GUIDE,
                ],
            );

            $count = 0;

            if ($rows) {
                $count = \count($rows);
            }

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }
}
