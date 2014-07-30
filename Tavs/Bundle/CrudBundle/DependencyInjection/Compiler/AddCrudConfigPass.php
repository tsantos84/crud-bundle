<?php

namespace Tavs\Bundle\CrudBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Class AddCrudConfigPass
 * @package Tavs\Bundle\CrudBundle\DependencyInjection\Compiler
 */
class AddCrudConfigPass implements CompilerPassInterface
{
    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('tavs_crud.manager')) {
            return;
        }

        $definition = $container->getDefinition('tavs_crud.manager');

        foreach ($container->getParameterBag()->get('crud_resources') as $name => $options) {
            $options['name'] = $name;
            $definition->addMethodCall('addCrudConfig', array($name, $options));
        }
    }
}
