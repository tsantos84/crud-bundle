<?php

namespace Tavs\Bundle\CrudBundle\Controller;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Interface ConfigurationInterface
 * @package Tavs\Bundle\CrudBundle\Controller
 */
interface ConfigurationInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return string
     */
    public function getEntityName();

    /**
     * @return string
     */
    public function getEntityAlias();

    /**
     * @return EntityRepository
     */
    public function getRepository();

    /**
     * @return string
     */
    public function getDataTable();

    /**
     * @param $name
     * @return string
     */
    public function getTemplate($name);

    /**
     * @param $intention
     * @return string
     */
    public function getRoute($intention);

    /**
     * @return FormTypeInterface
     */
    public function getFormType();

    /**
     * @return array
     */
    public function getFormTypeOptions();
}