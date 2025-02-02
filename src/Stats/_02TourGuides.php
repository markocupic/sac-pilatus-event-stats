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

use Contao\CalendarEventsModel;
use Contao\StringUtil;
use Contao\UserModel;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\SacEventToolBundle\Util\CalendarEventsUtil;
use Markocupic\SacEventToolBundle\Config\EventType;
use Markocupic\SacEventToolBundle\Config\TourguideQualification;
use Markocupic\SacPilatusEventStats\Data\DataItem;
use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;
use Markocupic\SacPilatusEventStats\Util\EventReleaseLevelUtil;

readonly class _02TourGuides
{
    public function __construct(
        private Connection $connection,
        private EventReleaseLevelUtil $eventReleaseLevelUtil,
    ) {
    }

    /**
     * @param array<TimePeriod> $timePeriods
     *
     * @throws Exception
     *
     * @return array<DataItem>
     */
    public function countTourGuides(array $timePeriods, array $arrAcceptedReleaseLevels, bool|null $isMountainGuide = null): array
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
                ->andWhere('t.eventType != ? AND t.eventType != ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::GENERAL_EVENT,
                    EventType::LAST_MINUTE_TOUR,
                ])
                ->fetchFirstColumn()
                ;

            $arrInstructorIds = [];

            foreach ($arrEventIds as $eventId) {
                $event = CalendarEventsModel::findByPk($eventId);

                if (null === $isMountainGuide) {
                    $arrInstructorIds = array_merge($arrInstructorIds, CalendarEventsUtil::getInstructorsAsArray($event, ['includeDisabled' => true]));
                } else {
                    $arrIds = CalendarEventsUtil::getInstructorsAsArray($event, ['includeDisabled' => true]);

                    foreach ($arrIds as $userId) {
                        if (null !== ($user = UserModel::findByPk($userId))) {
                            $arrTourGuideQualifications = StringUtil::deserialize($user->leiterQualifikation, true);

                            if (true === $isMountainGuide) { // User must be a mountain guide
                                if (\in_array(TourguideQualification::MOUNTAIN_GUIDE, array_map('intval', $arrTourGuideQualifications), true)) {
                                    $arrInstructorIds[] = $userId;
                                }
                            } elseif (false === $isMountainGuide) { // User must not be a mountain guide
                                if (!\in_array(TourguideQualification::MOUNTAIN_GUIDE, array_map('intval', $arrTourGuideQualifications), true)) {
                                    $arrInstructorIds[] = $userId;
                                }
                            }
                        }
                    }
                }
            }

            $count = \count(array_filter(array_unique($arrInstructorIds)));

            $data[] = new DataItem($timePeriod, $count);
        }

        return $data;
    }
}
