<?php

namespace BornFree\TacticianDomainEventBundle;

use BornFree\TacticianDomainEventBundle\DependencyInjection\Compiler\PopulateDebugCommandPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TacticianDomainEventBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PopulateDebugCommandPass());
    }
}
