<?php

namespace Tavs\Bundle\CrudBundle\Controller;

use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Tavs\Bundle\CrudBundle\Event\CrudEvent;
use Tavs\Bundle\CrudBundle\Event\CrudEvents;
use Tavs\Bundle\CrudBundle\Event\FormEvent;
use Tavs\Bundle\CrudBundle\Event\QueryEvent;
use Tavs\Bundle\CrudBundle\Event\RenderEvent;
use Tavs\DataTable\DataTableInterface;
use Tavs\DataTable\Twig\Extension\DataTableExtension;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class Controller
 * @package Tavs\Bundle\CrudBundle\Controller
 */
class Controller extends ContainerAware
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

     /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Shows the records of a given entity
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $configuration = $this->getConfiguration();

        $dataTable = $this
            ->getDataTableFactory()
            ->createDataTable($configuration->getDataTable());

        // retorna a resposta ajax com os resultados do datatable
        if ($request->isXmlHttpRequest()) {
            return $this->handleDataTableResponse($dataTable, $configuration);
        }

        $view = new \ArrayObject([
            'crud' => $configuration,
            'datatable' => $dataTable->createView()
        ]);

        $view = $this->trigger(CrudEvents::CRUD_BEFORE_RENDER_INDEX, new RenderEvent($configuration, $view))->getView();

        return $this->render($configuration->getTemplate('index'), $view->getArrayCopy());
    }

    /**
     * Display the create form and save a new record of a given entity
     */
    public function createAction()
    {
        $configuration = $this->getConfiguration();

        // create the form and data
        $data = $this->createEntity();
        $form = $this->createForm($configuration->getFormType(), $data, [
            'action' => $this->generateUrl($configuration->getRoute('save'), [
                'id' => 0
            ])
        ]);

        $event = new FormEvent($configuration, $form, $data);
        $this->trigger(CrudEvents::CRUD_AFTER_CREATE_FORM_CREATE, $event);

        $data = $event->getData();
        $form->setData($data);

        $view = new \ArrayObject([
            'form' => $event->getForm()->createView(),
            'entity' => $data,
            'crud' => $configuration,
            'form_mode' => 'create',
            'edit_mode' => false,
            'create_mode' => true
        ]);

        $event = new RenderEvent($configuration, $view);
        $view = $this->trigger(CrudEvents::CRUD_BEFORE_RENDER_CREATE_FORM, $event)->getView();

        return $this->render($configuration->getTemplate('form'), $view->getArrayCopy());
    }

    /**
     * Display the edit form and save an existing record of a given entity
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request)
    {
        $configuration = $this->getConfiguration();

        $identifiers = $this->getIdentifiers($request);

        // retrieve the entity
        if (null === ($data = $this->getEntity($identifiers))) {
            throw new NotFoundHttpException('No entity was found');
        }

        // create the form and data
        $form = $this->createForm($configuration->getFormType(), $data, [
            'action' => $this->generateUrl($configuration->getRoute('save'), $identifiers)
        ]);

        $event = new FormEvent($configuration, $form, $data);
        $this->trigger(CrudEvents::CRUD_AFTER_CREATE_FORM_EDIT, $event);

        $data = $event->getData();
        $form->setData($data);

        $view = new \ArrayObject([
            'form' => $event->getForm()->createView(),
            'entity' => $data,
            'crud' => $configuration,
            'form_mode' => 'edit',
            'edit_mode' => true,
            'create_mode' => false
        ]);

        $event = new RenderEvent($configuration, $view);
        $view = $this->trigger(CrudEvents::CRUD_BEFORE_RENDER_EDIT_FORM, $event)->getView();

        return $this->render($configuration->getTemplate('form'), $view->getArrayCopy());
    }

    /**
     * @param Request $request
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function saveAction(Request $request)
    {
        $configuration = $this->getConfiguration();

        // retrieve the entity
        if ($id = $request->attributes->getInt('id')) {
            $identifiers = $this->getIdentifiers($request);
            if (null !== ($data = $this->getEntity($identifiers))) {
                $isUpdate = true;
            } else {
                throw new ResourceNotFoundException('Resource with id ('.$id.') not found');
            }
        } else {
            $data = $this->createEntity();
            $isUpdate = false;
        }

        // generate edit uri to redirect
        $editUri = $this->generateUrl(
            $configuration->getRoute('edit'), ['id' => $id]
        );

        // create the form and data
        $form = $this->createForm($configuration->getFormType(), $data);

        // accepts only POST request
        if ($request->isMethod('post')) {

            // detect the event names [INSER/UPDATE]
            if ($isUpdate) {
                $handlerEventName = CrudEvents::CRUD_BEFORE_HANDLER_EDIT_REQUEST;
                $beforeEventName = CrudEvents::CRUD_BEFORE_UPDATE;
                $afterEventName = CrudEvents::CRUD_AFTER_UPDATE;
            } else {
                $handlerEventName = CrudEvents::CRUD_BEFORE_HANDLER_CREATE_REQUEST;
                $beforeEventName = CrudEvents::CRUD_BEFORE_INSERT;
                $afterEventName = CrudEvents::CRUD_AFTER_INSERT;
            }

            // crud.before_handler_[edit|create]_request
            $this->trigger($handlerEventName, new FormEvent($configuration, $form, $data));

            // process the form
            $form->handleRequest($request);

            // valida o formulário
            if ($form->isValid()) {

                // begin transaction
                $em = $this->getEntityManager();

                // add the entity to the unit of work queue
                $conn = $em->getConnection();
                $conn->beginTransaction();

                try {

                    // crud.before[insert|update]
                    $this->trigger($beforeEventName, new FormEvent($configuration, $form, $data));

                    // persist the data
                    $em->persist($data);
                    $em->flush();
                    $conn->commit();

                    // crud.after[insert|update]
                    $this->trigger($afterEventName, new FormEvent($configuration, $form, $data));

                    // redirect to index
                    $response = new RedirectResponse($this->generateUrl($configuration->getRoute('index')));

                } catch (\Exception $e) {

                    $conn->rollBack();

                    // crud.after[insert|update]
                    $this->trigger(CrudEvents::CRUD_SAVE_ERROR, new FormEvent($configuration, $form, $data));

                    // todo: esta ação deveria ser executada no listener, e não no controller
                    $request->getSession()->getFlashBag()->add('danger', $e->getMessage());

                    $view = new \ArrayObject([
                        'form' => $form->createView(),
                        'entity' => $data,
                        'form_mode' => $isUpdate ? 'edit' : 'create',
                        'edit_mode' => $isUpdate,
                        'create_mode' => $isUpdate,
                        'crud' => $configuration,
                    ]);

                    $response = $this->render($configuration->getTemplate('form'), $view->getArrayCopy());
                }

            } else {

                $view = new \ArrayObject([
                    'form' => $form->createView(),
                    'crud' => $configuration,
                    'entity' => $data
                ]);

                $response = $this->render($configuration->getTemplate('form'), $view->getArrayCopy());
            }

        } else {
            $response = new RedirectResponse($editUri);
        }

        return $response;
    }

    /**
     * Delete an existing row of a given entity
     */
    public function deleteAction()
    {

    }

    /**
     * @param $type
     * @param null $data
     * @param array $options
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    protected function createForm($type, $data = null, array $options = [])
    {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * @param $name
     * @param array $params
     * @param bool $referenceType
     * @return string
     */
    protected function generateUrl($name, array $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($name, $params, $referenceType);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager()
    {
        return $this->container->get('doctrine.orm.entity_manager');
    }

    /**
     * Dispara um evento do crud
     *
     * @param $name
     * @param $ev
     * @return CrudEvent
     */
    private function trigger($name, $ev)
    {
        return $this->dispatcher->dispatch($this->eventName($name), $ev);
    }

    /**
     * @param $event
     * @return string
     */
    private function eventName($event)
    {
        $configuration = $this->getConfiguration();
        return sprintf('%s.%s', $event, $configuration->getName());
    }

    /**
     * @param $template
     * @param array $view
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function render($template, array $view = [])
    {
        return $this->container->get('templating')->renderResponse($template, $view);
    }

    /**
     * @param DataTableInterface $dataTable
     * @param ConfigurationInterface $configuration
     * @return JsonResponse
     */
    private function handleDataTableResponse(DataTableInterface $dataTable, ConfigurationInterface $configuration)
    {
        $query = $configuration->getRepository()->createQueryBuilder($configuration->getEntityAlias());
        $query = $this->trigger(CrudEvents::CRUD_AFTER_CREATE_QUERY, new QueryEvent($configuration, $query))->getQuery();

        $request = $this->container->get('request');

        $dataTable
            ->setDataSource($this->getDataTableFactory()->createDataSource($query))
            ->handleRequest($request);

        $twig = $this->container->get('twig');
        $twig->initRuntime();

        // create the view
        $view = $dataTable->createView();

        /** @var DataTableExtension $extension */
        $extension = $twig->getExtension('datatable');

        try {
            // has the resource some theme?
//            $extension->setTheme($view, array($configuration->getTemplate('datatable_theme')));
        } catch (\InvalidArgumentException $e) {
        }

        $response = new JsonResponse($extension->getDataTableResponse($view, $request));
        $response->headers->set('content-length', strlen($response->getContent()));

        return $response;
    }

    /**
     * @return \Tavs\DataTable\DataTableFactory
     */
    private function getDataTableFactory()
    {
        return $this->container->get('datatable.factory');
    }

    /**
     * @return ConfigurationInterface
     */
    private function getConfiguration()
    {
        $request = $this->container->get('request');

        return $this->container->get('tavs_crud.manager')->getCrudConfig(
            $request->attributes->get('_resource')
        );
    }

    /**
     * @return object
     */
    private function createEntity()
    {
        $class = $this->getConfiguration()->getRepository()->getClassName();
        return new $class();
    }

    private function getIdentifiers(Request $request)
    {
        $em = $this->getEntityManager();
        $repository = $this->getConfiguration()->getRepository();
        $metadata = $em->getClassMetadata($repository->getClassName());
        $fieldNames = $metadata->getIdentifierFieldNames();

        $identifiers = [];

        foreach ($fieldNames as $field) {
            if ($value = $request->get($field)) {
                $identifiers[$field] = $value;
            }
        }

        return $identifiers;
    }

    private function getEntity(array $identifiers)
    {
        $repository = $this->getConfiguration()->getRepository();
        $entity = call_user_func_array([$repository, 'find'], $identifiers);
        return $entity;
    }
}