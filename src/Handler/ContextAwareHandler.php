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
    function __invoke(array $event, array $context): array;
}