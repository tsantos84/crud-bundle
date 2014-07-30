<?php

namespace Tavs\Bundle\CrudBundle;

use Tavs\Bundle\CrudBundle\DependencyInjection\Compiler\AddCrudConfigPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class TavsCrudBundle
 * @package Tavs\Bundle\CrudBundle
 */
class TavsCrudBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new AddCrudConfigPass());
    }

}
