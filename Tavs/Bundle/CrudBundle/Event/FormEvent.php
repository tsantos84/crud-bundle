<?php

namespace Tavs\Bundle\CrudBundle\Event;

use Tavs\Bundle\CrudBundle\Controller\ConfigurationInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class FormEvent
 * @package Tavs\Bundle\CrudBundle\Event
 */
class FormEvent extends CrudEvent
{
    /**
     * @var \Symfony\Component\Form\FormInterface
     */
    private $form;

    /**
     * @var object
     */
    private $data;

    /**
     * @param ConfigurationInterface $configuration
     * @param FormInterface $form
     * @param $data
     */
    public function __construct(ConfigurationInterface $configuration, FormInterface $form, $data)
    {
        parent::__construct($configuration);
        $this->form = $form;
        $this->data = $data;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return object
     */
    public function getData()
    {
        return $this->data;
    }
}