<?php

namespace Tavs\Bundle\CrudBundle\Manager;

use Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface;
use Tavs\Bundle\CrudBundle\Factory\ConfigurationFactory;

/**
 * Class CrudManager
 * @package Tavs\Bundle\CrudBundle\Manager
 */
class CrudManager
{
    /**
     * @var array
     */
    private $configurations = array();

    /**
     * @var ConfigurationFactory
     */
    private $factory;

    /**
     * @param ConfigurationFactory $factory
     */
    public function __construct(ConfigurationFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $name
     * @param array|ConfigurationInterface $config
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addCrudConfig($name, $config)
    {
        if (is_array($config) || $config instanceof ConfigurationInterface) {
            $this->configurations[$name] = $config;
            return $this;
        }

        throw new \InvalidArgumentException(
            '$config should be array or an instance of Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface, ' .
            is_object($config) ? get_class($config) : gettype($config) . ' given'
        );
    }

    /**
     * @param $name
     * @return ConfigurationInterface
     * @throws \InvalidArgumentException
     */
    public function getCrudConfig($name)
    {
        if (array_key_exists($name, $this->configurations)) {

            // lazy create crud controller configuration
            if (is_array($this->configurations[$name])) {
                $this->configurations[$name] = $this->factory->createConfiguration($this->configurations[$name]);
            }

            return $this->configurations[$name];
        }

        throw new \InvalidArgumentException('no resource configuration found for ' . $name);
    }

}