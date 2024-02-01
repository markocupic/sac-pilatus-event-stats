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

namespace Markocupic\SacPilatusEventStats\Util;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventToolBundle\Model\EventReleaseLevelPolicyModel;

readonly class EventReleaseLevelUtil
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    /**
     * @param array $arrAcceptedReleaseLevels<int>
     *
     * @throws Exception
     *
     * @return array<int>
     */
    public function getAllowedEventReleaseLevelIds(array $arrAcceptedReleaseLevels, string|null $eventType = null): array
    {
        $qb = $this->connection->createQueryBuilder();

        $qb->select('eventReleaseLevel')
            ->from('tl_calendar_events', 't')
            ->where('id > 0')
        ;

        // event type filter
        if (null !== $eventType && \strlen($eventType)) {
            $qb->andWhere(sprintf('eventType = "%s"', $eventType));
        }

        $qb->groupBy('eventReleaseLevel');

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
