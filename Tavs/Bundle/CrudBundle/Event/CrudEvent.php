<?php

namespace Tavs\Bundle\CrudBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CrudEvent
 *
 * @package Tavs\Bundle\CrudBundle\Event
 */
class CrudEvent extends Event
{
    /**
     * @var FormInterface
     */
    private $form;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var \ArrayObject
     */
    private $viewBag;

    /**
     * @var QueryBuilder
     */
    private $query;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @param FormInterface $form
     * @param null          $data
     * @param \ArrayObject  $viewBag
     * @param QueryBuilder  $query
     */
    public function __construct(
        FormInterface $form = null,
        $data = null,
        Response $response = null,
        \ArrayObject $viewBag = null,
        QueryBuilder $query = null,
        \Exception $exception = null
    )
    {
        $this->form = $form;
        $this->data = $data;
        $this->response = $response;
        $this->viewBag = $viewBag;
        $this->query = $query;
        $this->exception = $exception;
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param FormInterface $form
     */
    public function setForm($form)
    {
        $this->form = $form;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return \ArrayObject
     */
    public function getViewBag()
    {
        return $this->viewBag;
    }

    /**
     * @param \ArrayObject $viewBag
     */
    public function setViewBag($viewBag)
    {
        $this->viewBag = $viewBag;
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @param QueryBuilder $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }


}