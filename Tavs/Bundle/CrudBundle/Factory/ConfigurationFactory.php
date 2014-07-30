<?php

namespace Tavs\Bundle\CrudBundle\Factory;

use Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface;
use Tavs\Bundle\CrudBundle\Controller\Configuration;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class Factory
 * @package Dife\Component\Crud
 */
class ConfigurationFactory extends ContainerAware
{
    /**
     * @param array $options
     * @return Configuration
     */
    public function createConfiguration(array $options = array())
    {
        $configuration = new Configuration($this->resolveOptions($options));
        return $configuration;
    }

    /**
     * @param array $options
     * @return array
     */
    public function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $container = $this->container;

        $serviceResolver = function(Options $options, $value) use ($container) {
            if (is_string($value) && 0 === strpos($value, '@')) {
                $value = $container->get(substr($value, 1));
            }
            return $value;
        };

        $resolver->setDefaults(array(
            'repository' => null,
            'routes' => array()
        ));

        $resolver->setRequired(array(
            'name',
            'datatable',
            'entity',
            'form',
            'templates'
        ));

        $resolver->setNormalizers(array(
            'entity' => function(Options $options, $value) {
                if (is_string($value)) {
                    $value = array(
                        'name' => $value,
                        'alias' => '_main_'
                    );
                }
                return $value;
            },
            'datatable' => $serviceResolver,
            'form' => $serviceResolver,
            'repository' => function(Options $options, $value) use ($container) {
                if (is_string($value) && 0 === strpos($value, '@')) {
                    $value = $container->get(substr($value, 1));
                } elseif (null === $value) {
                    $value = $container->get('doctrine.orm.entity_manager')->getRepository($options['entity']['name']);
                }
                return $value;
            }
        ));

        $resolver->setAllowedTypes(array(
            'entity' => 'array',
            'repository' => 'Doctrine\ORM\EntityRepository',
            'form' => 'Symfony\Component\Form\FormTypeInterface'
        ));

        return $resolver->resolve($options);
    }
}