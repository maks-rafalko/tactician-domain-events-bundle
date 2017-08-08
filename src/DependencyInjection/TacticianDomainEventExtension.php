<?php

namespace BornFree\TacticianDomainEventBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TacticianDomainEventExtension extends Extension implements CompilerPassInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('tactician_domain_events.dispatcher')) {
            return;
        }

        $this->addListeners($container);
        $this->addSubscribers($container);
    }

    private function addListeners(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tactician_domain_events.dispatcher');
        $taggedServices = $container->findTaggedServiceIds('tactician.event_listener');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['event'])) {
                    throw new \Exception('The tactician.event_listener tag must always have an event attribute');
                }

                if (!class_exists($attributes['event'])) {
                    throw new \Exception(
                        sprintf(
                            'Class %s registered as an event class in %s does not exist',
                            $attributes['event'],
                            $id
                        )
                    );
                }

                if (!isset($attributes['method'])) {
                    throw new \Exception('The tactician.event_listener tag must always have an method attribute');
                }

                $definition->addMethodCall('addListener', [
                    $attributes['event'],
                    [new Reference($id), $attributes['method']]
                ]);
            }
        }
    }

    private function addSubscribers(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('tactician_domain_events.dispatcher');
        $taggedServices = $container->findTaggedServiceIds('tactician.event_subscriber');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addSubscriber', [
                new Reference($id)
            ]);
        }
    }
}
