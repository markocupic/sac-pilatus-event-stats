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
use Markocupic\SacEventToolBundle\CalendarEventsHelper;
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
                ->where('t.startDate >= ?')
                ->andWhere('t.startDate <= ?')
                ->andWhere('t.eventType != ?')
                ->andWhere('t.eventType != ?')
                ->andWhere($qb->expr()->in('t.eventReleaseLevel', $arrAcceptedReleaseLevelIds))
                ->setParameters([
                    $timePeriod->getStartTime(),
                    $timePeriod->getEndTime(),
                    EventType::GENERAL_EVENT,
                    EventType::LAST_MINUTE_TOUR,
                ])
                ->fetchFirstColumn()
                ;

            $intInstructors = 0;

            foreach ($arrEventIds as $eventId) {
                $event = CalendarEventsModel::findByPk($eventId);

                if (null === $isMountainGuide) {
                    $intInstructors += \count(CalendarEventsHelper::getInstructorsAsArray($event, false));
                } else {
                    $arrIds = CalendarEventsHelper::getInstructorsAsArray($event, false);

                    foreach ($arrIds as $userId) {
                        if (null !== ($user = UserModel::findByPk($userId))) {
                            $arrTourGuideQualifications = StringUtil::deserialize($user->leiterQualifikation, true);

                            if (true === $isMountainGuide) { // User must be a mountain guide
                                if (\in_array(TourguideQualification::MOUNTAIN_GUIDE, array_map('intval', $arrTourGuideQualifications), true)) {
                                    ++$intInstructors;
                                }
                            } elseif (false === $isMountainGuide) { // User must not be a mountain guide
                                if (!\in_array(TourguideQualification::MOUNTAIN_GUIDE, array_map('intval', $arrTourGuideQualifications), true)) {
                                    ++$intInstructors;
                                }
                            }
                        }
                    }
                }
            }

            $data[] = new DataItem($timePeriod, $intInstructors);
        }

        return $data;
    }
}
