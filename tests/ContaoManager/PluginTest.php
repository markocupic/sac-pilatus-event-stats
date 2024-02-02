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

namespace Markocupic\SacPilatusEventStats\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\DelegatingParser;
use Contao\TestCase\ContaoTestCase;
use Markocupic\SacEventToolBundle\MarkocupicSacEventToolBundle;
use Markocupic\SacPilatusEventStats\ContaoManager\Plugin;
use Markocupic\SacPilatusEventStats\MarkocupicSacPilatusEventStats;

class PluginTest extends ContaoTestCase
{
    public function testInstantiation(): void
    {
        $this->assertInstanceOf(Plugin::class, new Plugin());
    }

    public function testGetBundles(): void
    {
        $plugin = new Plugin();

        /** @var array $bundles */
        $bundles = $plugin->getBundles(new DelegatingParser());

        $this->assertCount(1, $bundles);
        $this->assertInstanceOf(BundleConfig::class, $bundles[0]);
        $this->assertSame(MarkocupicSacPilatusEventStats::class, $bundles[0]->getName());
        $this->assertSame([
            ContaoCoreBundle::class,
            MarkocupicSacEventToolBundle::class,
        ], $bundles[0]->getLoadAfter());
    }
}
