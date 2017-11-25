<?php

namespace BornFree\TacticianDomainEventBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugCommand extends Command
{
    /**
     * @var array
     */
    private $mappings;

    public function __construct(array $mappings)
    {
        parent::__construct();

        $this->mappings = $mappings;
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
