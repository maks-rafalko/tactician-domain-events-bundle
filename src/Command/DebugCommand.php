<?php

namespace BornFree\TacticianDomainEventBundle\Command;

use BornFree\TacticianDomainEvent\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DebugCommand extends Command
{
    /**
     * @var array
     */
    private $mappings;

    public function __construct(ContainerInterface $container, array $listeners, array $subscribers)
    {
        parent::__construct();

        $events = [];

        foreach ($listeners as $serviceId => $tags) {
            foreach ($tags as $tag) {
                $listener = $container->get($serviceId);
                $method = array_key_exists('method', $tag) ? $tag['method'] : '__invoke';

                $events[$tag['event']][] = get_class($listener) . '::' . $method;
            }
        }

        foreach ($subscribers as $serviceId => $tags) {
            $subscriber = $container->get($serviceId);

            if (!$subscriber instanceof EventSubscriberInterface) {
                continue;
            }

            foreach ($subscriber->getSubscribedEvents() as $event => $method) {
                $events[$event][] = get_class($subscriber) . '::' . $method[1];
            }
        }

        ksort($events);

        $this->mappings = $events;
    }

    protected function configure()
    {
        $this->setName('debug:tactician-domain-events');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Tactician domain events');
        $headers = ['Order', 'Callable'];

        foreach ($this->mappings as $event => $listeners) {
            $io->section('Event: '.$event);
            $io->table($headers, $this->mappingToRows($listeners));
        }

        $io->comment('Number of events: '. count($this->mappings));
    }

    private function mappingToRows(array $listeners)
    {
        $rows = [];
        foreach ($listeners as $idx => $listener) {
            $rows[] = ['#'.($idx+1), $listener];
        }

        return $rows;
    }
}
