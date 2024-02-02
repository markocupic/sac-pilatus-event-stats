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

namespace Markocupic\SacPilatusEventStats\Data;

use Markocupic\SacPilatusEventStats\TimePeriod\TimePeriod;

readonly class DataItem
{
    public function __construct(
        private TimePeriod $timePeriod,
        private array|int|string $data,
    ) {
    }

    public function getTimePeriod(): TimePeriod
    {
        return $this->timePeriod;
    }

    public function getData(): array|int|string
    {
        return $this->data;
    }
}
