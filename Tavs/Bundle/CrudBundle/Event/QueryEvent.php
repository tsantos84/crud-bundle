<?php

namespace Tavs\Bundle\CrudBundle\Event;
use Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Class QueryEvent
 * @package Tavs\Bundle\CrudBundle\Event
 */
class QueryEvent extends CrudEvent
{
    /**
     * @var \Doctrine\ORM\QueryBuilder
     */
    private $query;

    /**
     * @param ConfigurationInterface $configuration
     * @param QueryBuilder $query
     */
    public function __construct(ConfigurationInterface $configuration, QueryBuilder $query)
    {
        parent::__construct($configuration);
        $this->query = $query;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }
}