<?php

namespace Tavs\Bundle\CrudBundle\Event;

use Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CrudEvent
 * @package Tavs\Bundle\CrudBundle\Event
 */
class CrudEvent extends Event
{
    /**
     * @var ConfigurationInterface
     */
    private $configuration;

    /**
     * @param ConfigurationInterface $configuration
     */
    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return ConfigurationInterface
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }
}