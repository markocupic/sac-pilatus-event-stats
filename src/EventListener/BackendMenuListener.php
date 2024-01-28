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

namespace Markocupic\SacPilatusEventStats\EventListener;

use Contao\CoreBundle\Event\ContaoCoreEvents;
use Contao\CoreBundle\Event\MenuEvent;
use Markocupic\SacPilatusEventStats\Controller\EventStatsController;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

#[AsEventListener(ContaoCoreEvents::BACKEND_MENU_BUILD, priority: -255)]
readonly class BackendMenuListener
{
    public function __construct(
        private RouterInterface $router,
        private RequestStack $requestStack,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $factory = $event->getFactory();
        $tree = $event->getTree();

        if ('mainMenu' !== $tree->getName()) {
            return;
        }

        $contentNode = $tree->getChild('sac_be_modules');

        $node = $factory
            ->createItem('event_stats')
            ->setUri($this->router->generate(EventStatsController::class))
            ->setLabel('SAC Pilatus Event Stats')
            ->setLinkAttribute('title', 'SAC Pilatus Event Statistik')
            ->setLinkAttribute('class', 'sac-pilatus-event-stats')
            ->setCurrent(EventStatsController::class === $this->requestStack->getCurrentRequest()->get('_controller'))
        ;

        $contentNode->addChild($node);
    }
}
