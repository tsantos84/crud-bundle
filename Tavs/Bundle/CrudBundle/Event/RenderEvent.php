<?php

namespace Tavs\Bundle\CrudBundle\Event;
use Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface;

/**
 * Class RenderEvent
 * @package Tavs\Bundle\CrudBundle\Event
 */
class RenderEvent extends CrudEvent
{
    /**
     * @var \ArrayObject
     */
    private $view;

    /**
     * @var string
     */
    private $intention;

    /**
     * @param ConfigurationInterface $configuration
     * @param \ArrayObject $view
     * @param $intention
     */
    public function __construct(ConfigurationInterface $configuration, \ArrayObject $view, $intention = null)
    {
        parent::__construct($configuration);
        $this->view = $view;
        $this->intention = $intention;
    }

    /**
     * @return mixed
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param \ArrayObject $view
     */
    public function setView(\ArrayObject $view)
    {
        $this->view = $view;
    }

    /**
     * @return string
     */
    public function getIntention()
    {
        return $this->intention;
    }
}