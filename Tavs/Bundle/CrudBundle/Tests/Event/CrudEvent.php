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
        
        $this->assertInstance('Symfony\Component\Form\FormInterface', $event->getForm());
        $this->assertInstance('stdClass', $event->getData());
        $this->assertInstance('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertInstance('\ArrayObject', $event->getViewBag());
        $this->assertInstance('Doctrine\ORM\QueryBuilder', $event->getQuery());
        $this->assertInstance('\Exception', $event->getException());
    }
}
