<?php

declare(strict_types=1);

namespace Zana\Mediator\Contracts;

use Zana\Enterprise\Application\Command;
use Zana\Enterprise\Application\Query;

/**
 * Mediator interface for CQRS.
 * 
 * Dispatches commands and queries to their registered handlers.
 * Provides a simple, clean API for the Application layer.
 */
interface MediatorInterface
{
    /**
     * Dispatch a command to its handler.
     *
     * @param Command $command
     * @return mixed
     */
    public function dispatch(Command $command): mixed;

    /**
     * Dispatch a query to its handler.
     *
     * @param Query $query
     * @return mixed
     */
    public function query(Query $query): mixed;
}

