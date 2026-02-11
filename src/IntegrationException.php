<?php

namespace AnourValar\EloquentJournal;

use AnourValar\HttpClient\Response;
use Exception;
use Illuminate\Database\Eloquent\Model;

class IntegrationException extends Exception
{
    /**
     * @var array
     */
    protected array $args;

    /**
     * @param string $event
     * @param \AnourValar\HttpClient\Response|array|null $response
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param array|string|null $tags
     * @return void
     */
    public function __construct(string $event, Response|array|null $response, ?Model $entity = null, array|string|null $tags = null)
    {
        $this->args = [$event, $entity, $response, false, $tags];
    }

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        \App::make(Service::class)->captureIntegration(...$this->args);
    }
}
