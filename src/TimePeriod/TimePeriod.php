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

namespace Markocupic\SacPilatusEventStats\TimePeriod;

readonly class TimePeriod
{
    public function __construct(
        private int $startTstamp,
        private int $endTstamp,
    ) {
    }

    public function getStartTime(): int
    {
        return $this->startTstamp;
    }

    public function getEndTime(): int
    {
        return $this->endTstamp;
    }

    public function getFormattedStartTime(string $format): string
    {
        return date($format, $this->startTstamp);
    }

    public function getFormattedEndTime(string $format): string
    {
        return date($format, $this->endTstamp);
    }
}
