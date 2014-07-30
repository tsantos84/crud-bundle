<?php

namespace Tavs\Bundle\CrudBundle\Event;

/**
 * Class CrudEvents
 * @package Tavs\Bundle\CrudBundle\Event
 */
final class CrudEvents
{
    // events for index action
    const CRUD_AFTER_CREATE_QUERY        = 'crud.after_create_query';
    const CRUD_BEFORE_RENDER_INDEX       = 'crud.before_render_index';

    // events for edit action
    const CRUD_AFTER_CREATE_FORM_EDIT    = 'crud.after_create_form_edit';
    const CRUD_BEFORE_RENDER_EDIT_FORM   = 'crud.before_render_edit_form';
    const CRUD_BEFORE_HANDLER_EDIT_REQUEST = 'crud.before_handler_edit_request';

    // events for create action
    const CRUD_AFTER_CREATE_FORM_CREATE  = 'crud.after_create_form_create';
    const CRUD_BEFORE_RENDER_CREATE_FORM = 'crud.before_render_create_form';
    const CRUD_BEFORE_HANDLER_CREATE_REQUEST = 'crud.before_handler_create_request';

    // events for saving action
    const CRUD_BEFORE_INSERT             = 'crud.before_insert';
    const CRUD_AFTER_INSERT              = 'crud.after_insert';
    const CRUD_BEFORE_UPDATE             = 'crud.before_update';
    const CRUD_AFTER_UPDATE              = 'crud.after_update';
    const CRUD_SAVE_ERROR                = 'crud.save_error';
}