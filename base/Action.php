<?php

namespace rcb\base;

abstract class Action extends BaseObject
{

    /**
     * Runs the action. Called by Application. Should be overridden in a derivative class.
     * @param array $parameters
     */
    public function run(array $parameters = []): void
    {
    }

}
