<?php

namespace Tavs\Bundle\CrudBundle\Tests\Event;

use Tavs\Bundle\CrudBundle\Event;

class CrudEventTest extend \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $form = $this->mock('Symfony\Component\Form\FormInterface');
        $data = new \stdClass();
        $response = $this->mock('Symfony\Component\HttpFoundation\Response');
        $viewBag = new \ArrayObject();
        $query = $this->mock('Doctrine\ORM\QueryBuilder');
        $exception = new \Exception();
        $event = new CrudEvent($form, $data, $viewBag, $query, $exception);
        
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $event->getForm());
        $this->assertInstanceOf('stdClass', $event->getData());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertInstanceOf('\ArrayObject', $event->getViewBag());
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $event->getQuery());
        $this->assertInstanceOf('\Exception', $event->getException());
    }
}
