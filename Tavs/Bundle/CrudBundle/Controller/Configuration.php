<?php

namespace Tavs\Bundle\CrudBundle\Controller;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Class Configuration
 * @package Tavs\Bundle\CrudBundle\Controller
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->options['name'];
    }

    /**
     * @inheritdoc
     */
    public function getEntityName()
    {
        return $this->options['entity']['name'];
    }

    /**
     * @inheritdoc
     */
    public function getEntityAlias()
    {
        return $this->options['entity']['alias'];
    }

    /**
     * @inheritdoc
     */
    public function getRepository()
    {
        return $this->options['repository'];
    }

    /**
     * @inheritdoc
     */
    public function getDataTable()
    {
        return $this->options['datatable'];
    }

    /**
     * @inheritdoc
     */
    public function getRoute($intention)
    {
        return $this->options['routes'][$intention];
    }

    /**
     * @inheritdoc
     */
    public function getTemplate($name)
    {
        if (array_key_exists($name, $this->options['templates'])) {
            return $this->options['templates'][$name];
        }

        throw new \InvalidArgumentException('the template named "' . $name . '" was not found');
    }

    /**
     * @inheritdoc
     */
    public function getFormType()
    {
        return $this->options['form']['type'];
    }

    /**
     * @inheritdoc
     */
    public function getFormTypeOptions()
    {
        return $this->options['form']['options'];
    }

}