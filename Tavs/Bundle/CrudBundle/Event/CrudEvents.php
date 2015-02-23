<?php

namespace Tavs\Bundle\CrudBundle\Event;

/**
 * Class CrudEvents
 *
 * @package Tavs\Bundle\CrudBundle\Event
 */
final class CrudEvents
{
    const ON_CREATE_QUERY = 'crud.on_create_query';
    const ON_RENDER_INDEX = 'crud.on_render_index';
    const ON_CREATE_FORM = 'crud.on_create_form';
    const ON_RENDER_FORM = 'crud.on_render_form';
    const PRE_HANDLER_FORM = 'crud.pre_handler_form';
    const POST_HANDLER_FORM = 'crud.post_handler_form';
    const ON_FORM_VALIDATION_FAILURE = 'crud.on_form_validation_failure';
    const PRE_SAVE_DATA = 'crud.pre_save';
    const POST_SAVE_DATA = 'crud.post_save';
    const ON_SAVE_FAILURE = 'crud.save_failure';
    const PRE_DELETE_DATA = 'crud.pre_delete_data';
    const POST_DELETE_DATA = 'crud.post_delete_data';
}