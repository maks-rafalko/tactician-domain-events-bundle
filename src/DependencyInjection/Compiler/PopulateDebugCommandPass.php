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

        $events = [];

        foreach ($listeners as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $listener = $container->getDefinition($serviceId);
                $events[$tag['event']][] = $listener->getClass().'::'.$tag['method'];
            }
        }

        foreach ($subscribers as $serviceId => $tags) {
            $subscriber = $container->get($serviceId);

            if (!$subscriber instanceof EventSubscriberInterface) {
                continue;
            }

            foreach ($subscriber->getSubscribedEvents() as $event => $method) {
                $events[$event][] = get_class($subscriber).'::'.$method;
            }
        }

        ksort($events);
        $command->setArgument(0, $events);
    }
}
