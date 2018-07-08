<?php

namespace BornFree\TacticianDomainEventBundle\DependencyInjection\Compiler;

use BornFree\TacticianDomainEvent\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PopulateDebugCommandPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('tactician_domain_events.command.debug')) {
            return;
        }

        $command = $container->getDefinition('tactician_domain_events.command.debug');
        $subscribers = $container->findTaggedServiceIds('tactician.event_subscriber');
        $listeners = $container->findTaggedServiceIds('tactician.event_listener');

        $command->setArgument(1, $listeners);
        $command->setArgument(2, $subscribers);
    }
}
