<?php
/**
 * Created by PhpStorm.
 * User: cccruceru
 * Date: 4/18/2019
 * Time: 1:45 PM
 */

namespace Bref\Handler;


interface ContextAwareHandler
{
    /**
     * @param array $event
     * @param array $context
     *
     * @return mixed Anything that can be serialized to JSON
     */
    function __invoke(array $event, array $context);
}