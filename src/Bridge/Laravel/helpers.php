<?php

if (!function_exists('runningInLambda')) {
    /**
     * Heps us check to see if we are running in a Lambda context
     * or not.
     * @return bool
     */
    function runningInLambda(): bool
    {
        return getenv('AWS_EXECUTION_ENV') !== false;
    }
}
