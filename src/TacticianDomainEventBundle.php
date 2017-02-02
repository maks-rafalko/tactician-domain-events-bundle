<?php

namespace BornFree\TacticianDomainEventBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TacticianDomainEventBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
//        $container->addCompilerPass(new CommandHandlerPass());
    }

//    public function getContainerExtension()
//    {
//        return new TacticianExtension();
//    }
}