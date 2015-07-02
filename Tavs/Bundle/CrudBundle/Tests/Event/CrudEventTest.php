<?php

namespace Tavs\Bundle\CrudBundle\Tests\Event;

use Tavs\Bundle\CrudBundle\Event\CrudEvent;

class CrudEventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $form = $this->getFormMock();
        $data = $this->getData();
        $response = $this->getResponseMock();
        $viewBag = $this->getViewBag();
        $query = $this->getQueryBuilderMock();
        $exception = $this->getException();
        $event = new CrudEvent($form, $data, $response, $viewBag, $query, $exception);
        
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $event->getForm());
        $this->assertInstanceOf('stdClass', $event->getData());
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $event->getResponse());
        $this->assertInstanceOf('\ArrayObject', $event->getViewBag());
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $event->getQuery());
        $this->assertInstanceOf('\Exception', $event->getException());
    }
    
    private function getFormMock()
    {
        return $this->getMock('Symfony\Component\Form\FormInterface');
    }
    
    private function getData()
    {
        return new \stdClass();
    }
    
    private function getResponseMock()
    {
        return $this->getMock('Symfony\Component\HttpFoundation\Response');
    }
    
    private function getQueryBuilderMock()
    {
        $qb = $this
            ->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
            
        return $qb;
    }
    
    private function getViewBag()
    {
        return new \ArrayObject();
    }
    
    private function getException()
    {
        return new \Exception();
    }
}
