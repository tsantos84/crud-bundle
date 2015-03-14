<?php

namespace Tavs\Bundle\CrudBundle\Controller;

use Doctrine\ORM\UnitOfWork;
use Tavs\Bundle\CrudBundle\Event\CrudEvent;
use Tavs\Bundle\CrudBundle\Event\CrudEvents;
use Tavs\Bundle\CrudBundle\Event\EntityEvent;
use Tavs\Bundle\CrudBundle\Event\FailureEntitySaveEvent;
use Tavs\Bundle\CrudBundle\Event\FormEvent;
use Tavs\Bundle\CrudBundle\Event\GetResponseEvent;
use Tavs\Bundle\CrudBundle\Event\QueryEvent;
use Tavs\Bundle\CrudBundle\Event\RenderEvent;
use Tavs\DataTable\DataTableInterface;
use Tavs\DataTable\Twig\Extension\DataTableExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class Controller
 *
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
            'crud'      => $configuration,
            'datatable' => $dataTable->createView()
        ]);

        $event = $this->trigger(CrudEvents::ON_RENDER_INDEX, ['viewBag' => $view]);

        // in case of the listener builds the response
        if ($response = $event->getResponse()) {
            return $response;
        }

        $view = $event->getViewBag();

        return $this->render($configuration->getTemplate('index'), $view->getArrayCopy());
    }

    /**
     * Display the create form and save a new record of a given entity
     */
    public function createAction()
    {
        $configuration = $this->getConfiguration();

        // create a fresh entity
        $data = $this->createEntity();

        // create the form
        $form = $this->createCrudForm($data, ['id' => 0]);

        // return the response
        return $this->renderForm($form, $data);
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
        $identifiers = $this->getIdentifiers($request);
        $configuration = $this->getConfiguration();

        // retrieve the entity
        if (null === ($data = $this->getEntity($identifiers))) {
            throw new NotFoundHttpException('No entity was found');
        }

        // create the form
        $form = $this->createCrudForm($data, $identifiers);

        // return the response
        return $this->renderForm($form, $data);
    }

    /**
     * @param Request $request
     *
     * @throws \Symfony\Component\Routing\Exception\ResourceNotFoundException
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function saveAction(Request $request)
    {
        if (!$request->isMethod('post')) {
            throw new \BadRequestHttpException('This request accepts only POST request method');
        }

        $configuration = $this->getConfiguration();
        $identifiers = $this->getIdentifiers($request);

        // retrieve the entity
        if ($id = $request->attributes->getInt('id')) {
            if (null !== ($data = $this->getEntity($identifiers))) {
                $isUpdate = true;
            } else {
                throw new ResourceNotFoundException('Resource with id (' . $id . ') not found');
            }
        } else {
            $data = $this->createEntity();
            $isUpdate = false;
        }

        // generate edit uri to redirect
        $editUri = $this->generateUrl(
            $configuration->getRoute('edit'), $identifiers
        );

        // create the form
        $form = $this->createCrudForm($data, $identifiers);

        // crud.pre_handler_form
        $this->trigger(CrudEvents::PRE_HANDLER_FORM, ['form' => $form, 'data' => $data]);

        // process the form
        $form->handleRequest($request);

        // crud.post_handler_form
        $this->trigger(CrudEvents::POST_HANDLER_FORM, ['form' => $form, 'data' => $data]);

        // valida o formulário
        if ($form->isValid()) {

            $data = $form->getData();

            // begin transaction
            $em = $this->getEntityManager();

            // add the entity to the unit of work queue
            $conn = $em->getConnection();
            $conn->beginTransaction();

            try {

                // crud.pre_save_data
                $this->trigger(CrudEvents::PRE_SAVE_DATA, ['data' => $data, 'form' => $form]);

                // persist the data
                $em->persist($data);
                $em->flush();
                $conn->commit();

                // crud.post_save_data
                $event = $this->trigger(CrudEvents::POST_SAVE_DATA, ['data' => $data, 'form' => $form]);

                // redirect to index
                if (null === ($response = $event->getResponse())) {
                    $response = new RedirectResponse($this->generateUrl($configuration->getRoute('index')));
                }

            } catch (\Exception $e) {

                if ($conn->isTransactionActive()) {
                    $conn->rollBack();
                }

                // crud.on_save_failure
                $event = $this->trigger(CrudEvents::ON_SAVE_FAILURE, ['data' => $data, 'exception' => $e, 'form' => $form]);

                // redirect to index
                if (null === ($response = $event->getResponse())) {
                    $response = $this->renderForm($form, $data);
                }

            }

        } else {

            // crud.on_form_validation_failure
            $event = $this->trigger(CrudEvents::ON_FORM_VALIDATION_FAILURE, ['form' => $form, 'data' => $data]);

            // render the form with the failures
            if (null === ($response = $event->getResponse())) {
                $response = $this->renderForm($form, $data);
            }
        }

        return $response;
    }

    /**
     * Delete an existing row of a given entity
     *
     * @param Request $request
     */
    public function deleteAction(Request $request)
    {
        $configuration = $this->getConfiguration();

        $identifiers = $this->getIdentifiers($request);

        // retrieve the entity
        if (null === ($data = $this->getEntity($identifiers))) {
            throw new NotFoundHttpException('No entity was found');
        }

        // crud.before_delete
        $ev = $this->trigger(CrudEvents::PRE_DELETE_DATA, new CrudEvent($configuration, $data));

        if ($ev->isDefaultPrevented()) {
            return;
        }

        // begin transaction
        $em = $this->getEntityManager();

        $em->getConnection()->beginTransaction();

        try {
            $em->remove($data);
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();
        }

        // crud.after_delete
        $this->trigger(CrudEvents::POST_DELETE_DATA, new CrudEvent($configuration, $data));
    }

    /**
     * @param       $type
     * @param null  $data
     * @param array $options
     *
     * @return \Symfony\Component\Form\Form|\Symfony\Component\Form\FormInterface
     */
    protected function createForm($type, $data = null, array $options = [])
    {
        return $this->container->get('form.factory')->create($type, $data, $options);
    }

    /**
     * @param       $name
     * @param array $params
     * @param bool  $referenceType
     *
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
     * @return \Symfony\Component\Form\FormInterface
     */
    private function createCrudForm($data, array $identifiers)
    {
        $configuration = $this->getConfiguration();

        $form = $this->createForm($configuration->getFormType(), $data, [
            'action' => $this->generateUrl($configuration->getRoute('save'), $identifiers)
        ]);

        return $form;
    }

    /**
     * Dispara um evento do crud
     *
     * @param $name
     * @param $ev
     *
     * @return CrudEvent
     */
    private function trigger($name, array $options)
    {
        $ev = $this->createCrudEvent($options);

        // dispatch "specific" crud event
        $ev = $this->dispatcher->dispatch($this->eventName($name), $ev);

        // dispatch "global" crud event
        $ev = $this->dispatcher->dispatch($name, $ev);

        return $ev;
    }

    /**
     * @param $event
     *
     * @return string
     */
    private function eventName($event)
    {
        $configuration = $this->getConfiguration();

        return sprintf('%s.%s', $event, $configuration->getName());
    }

    /**
     * @param       $template
     * @param array $view
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function render($template, array $view = [])
    {
        return $this->container->get('templating')->renderResponse($template, $view);
    }

    /**
     * @param FormInterface $form
     * @param               $data
     * @param               $toEdit
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function renderForm(FormInterface $form, $data)
    {
        $isUpdate = $this->getEntityManager()->getUnitOfWork()->getEntityState($data, UnitOfWork::STATE_NEW) == UnitOfWork::STATE_MANAGED;

        $configuration = $this->getConfiguration();

        $event = $this->trigger(CrudEvents::ON_CREATE_FORM, ['form' => $form, 'data' => $data]);

        $data = $event->getData();

        if (!$form->isSubmitted()) {
            $form->setData($data);
        }

        $view = new \ArrayObject([
            'form'        => $event->getForm()->createView(),
            'entity'      => $data,
            'crud'        => $configuration,
            'form_mode'   => $isUpdate ? 'edit' : 'create',
            'edit_mode'   => $isUpdate,
            'create_mode' => !$isUpdate
        ]);

        $event = $this->trigger(CrudEvents::ON_RENDER_FORM, ['viewBag' => $view, ‘data’ => $data, ‘form’=>$form]);

        if ($response = $event->getResponse()) {
            return $response;
        }

        $view = $event->getViewBag();

        return $this->render($configuration->getTemplate('form'), $view->getArrayCopy());
    }

    /**
     * @param DataTableInterface     $dataTable
     * @param ConfigurationInterface $configuration
     *
     * @return JsonResponse
     */
    private function handleDataTableResponse(DataTableInterface $dataTable, ConfigurationInterface $configuration)
    {
        $query = $configuration->getRepository()->createQueryBuilder($configuration->getEntityAlias());
        $query = $this->trigger(CrudEvents::ON_CREATE_QUERY, ['query' => $query])->getQuery();

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

//        try {
//            // has the resource some theme?
////            $extension->setTheme($view, array($configuration->getTemplate('datatable_theme')));
//        } catch (\InvalidArgumentException $e) {
//        }

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

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getIdentifiers(Request $request)
    {
        $em = $this->getEntityManager();
        $repository = $this->getConfiguration()->getRepository();
        $metadata = $em->getClassMetadata($repository->getClassName());
        $fieldNames = $metadata->getIdentifierFieldNames();

        $identifiers = [];

        foreach ($fieldNames as $field) {
            if (null !== ($value = $request->get($field))) {
                $identifiers[ $field ] = $value;
            }
        }

        return $identifiers;
    }

    /**
     * @param array $identifiers
     *
     * @return mixed
     */
    private function getEntity(array $identifiers)
    {
        $repository = $this->getConfiguration()->getRepository();
        $entity = call_user_func_array([$repository, 'find'], $identifiers);

        return $entity;
    }

    /**
     * @param $data
     *
     * @return string
     */
    private function getEntityState($data)
    {
        return $data->getId() ? EntityEvent::STATE_PERSISTED : EntityEvent::STATE_NEW;
    }

    /**
     * @param array $options
     *
     * @return CrudEvent
     */
    private function createCrudEvent(array $options)
    {
        $options['form'] = isset($options['form']) ? $options['form'] : null;
        $options['data'] = isset($options['data']) ? $options['data'] : null;
        $options['response'] = isset($options['response']) ? $options['response'] : null;
        $options['viewBag'] = isset($options['viewBag']) ? $options['viewBag'] : null;
        $options['query'] = isset($options['query']) ? $options['query'] : null;
        $options['exception'] = isset($options['exception']) ? $options['exception'] : null;

        return new CrudEvent(
            $options['form'],
            $options['data'],
            $options['response'],
            $options['viewBag'],
            $options['query'],
            $options['exception']
        );
    }
}