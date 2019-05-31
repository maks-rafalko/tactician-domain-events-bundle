<?php

namespace BornFree\TacticianDomainEventBundle\DependencyInjection;

use ReflectionClass;
use BornFree\TacticianDoctrineDomainEvent\EventListener\CollectsEventsFromAllEntitiesManagedByUnitOfWork;
use BornFree\TacticianDoctrineDomainEvent\EventListener\CollectsEventsFromEntities;
use Symfony\Component\DependencyInjection\Definition;
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

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerEventCollector($container, $config['collect_from_all_managed_entities']);
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

        $listeners = array_merge(
            $this->findListenersForEvent($taggedServices),
            $this->findListenersForTypeHints($container, $taggedServices)
        );

        foreach($listeners as $item) {
            if (!class_exists($item['event'])) {
                throw new \Exception(
                    sprintf(
                        'Class %s registered as an event class in %s does not exist',
                        $item['event'],
                        $item['id']
                    )
                );
            }

            $listener = $item['method']
                ? [new Reference($item['id']), $item['method']]
                : new Reference($item['id']);

            $definition->addMethodCall('addListener', [
                $item['event'],
                $listener
            ]);

        }
    }

    private function findListenersForEvent($taggedServices): array
    {
        $listeners = [];

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['event'])) {
                    continue;
                }

                $listeners[] = [
                    'id'     => $id,
                    'event'  => $attributes['event'],
                    'method' => $attributes['method'] ?? null
                ];               
            }
        }

        return $listeners;
    }

    private function findListenersForTypeHints(ContainerBuilder $container, $taggedServices): array
    {
        $listeners = [];

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!isset($attributes['typehints']) || $attributes['typehints'] !== true) {
                    continue;
                }

                $reflClass = new ReflectionClass($container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass()));

                foreach ($reflClass->getMethods() as $method) {
                    if (!$method->isPublic()
                        || $method->isConstructor()
                        || $method->isStatic()
                        || $method->isAbstract()
                        || $method->isVariadic()
                        || $method->getNumberOfParameters() !== 1
                    ) {
                        continue;
                    }
                    $parameter = $method->getParameters()[0];
                    // dump($parameter);
                    if (!$parameter->hasType()
                        || $parameter->getType()->isBuiltin()
                        || $parameter->getClass()->isInterface()
                    ) {
                        continue;
                    }

                    $event = (string)$parameter->getType();

                    $listeners[] = [
                        'id'     => $id,
                        'event'  => $event,
                        'method' => $method->getName()
                    ];               
                }
            }
        }

        return $listeners;
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

    private function registerEventCollector(ContainerBuilder $container, $collectFromAllManagedEntities)
    {
        $class = CollectsEventsFromEntities::class;
        if ($collectFromAllManagedEntities) {
            $class = CollectsEventsFromAllEntitiesManagedByUnitOfWork::class;
        }

        $eventCollector = new Definition($class);
        $eventCollector->addTag('doctrine.event_subscriber', ['connection' => 'default']);

        $container->setDefinition('tactician_domain_events.doctrine.event_collector', $eventCollector);
    }
}
