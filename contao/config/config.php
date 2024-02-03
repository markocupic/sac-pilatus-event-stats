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

use Markocupic\SacPilatusEventStats\Controller\EventStatsController;

$GLOBALS['BE_MOD']['sac_be_modules'][EventStatsController::BACKEND_MODULE_TYPE] = [
    'hideInNavigation' => true,
];
