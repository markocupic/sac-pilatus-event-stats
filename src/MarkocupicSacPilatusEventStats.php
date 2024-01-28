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

namespace Markocupic\SacPilatusEventStats;

use Markocupic\SacPilatusEventStats\DependencyInjection\MarkocupicSacPilatusEventStatsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MarkocupicSacPilatusEventStats extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getContainerExtension(): MarkocupicSacPilatusEventStatsExtension
    {
        return new MarkocupicSacPilatusEventStatsExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
}
