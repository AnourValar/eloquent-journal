<?php

namespace AnourValar\EloquentJournal;

use AnourValar\HttpClient\Response;
use Illuminate\Database\Eloquent\Model;

class Service
{
    /**
     * DI
     *
     * @param \Illuminate\Foundation\Auth\User|null $user
     * @param string|null $ipAddress
     * @return void
     */
    public function __construct(protected ?\Illuminate\Foundation\Auth\User $user = null, protected ?string $ipAddress = null)
    {

    }

    /**
     * Set a "current" user
     *
     * @param \Illuminate\Foundation\Auth\User|null $user
     * @return self
     */
    public function user(?\Illuminate\Foundation\Auth\User $user): self
    {
        $clone = clone $this;
        $clone->user = $user;

        return $clone;
    }

    /**
     * Set a "current" IP
     *
     * @param string|null $ipAddress
     * @return self
     */
    public function ipAddress(?string $ipAddress): self
    {
        $clone = clone $this;
        $clone->ipAddress = $ipAddress;

        return $clone;
    }

    /**
     * Log a model change
     *
     * @param string $event
     * @param \Illuminate\Database\Eloquent\Model $entity
     * @param array|null $dataExtra
     * @param bool $success
     * @param array|string|null $tags
     * @return \AnourValar\EloquentJournal\Journal|null
     */
    public function captureModel(string $event, Model $entity, ?array $dataExtra = null, bool $success = true, array|string|null $tags = null): ?Journal
    {
        $data = \App::make(\AnourValar\EloquentJournal\Handlers\ModelType::class)->getData($entity, $event);
        if ($dataExtra) {
            $data = array_replace((array) $data, $dataExtra);
        }

        if ($data === null) {
            return null;
        }

        return $this->create([
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getIpAddress(),
            'entity' => $this->getEntity($entity),
            'entity_id' => $entity->getKey(),
            'type' => 'model',
            'event' => $event,
            'data' => $data,
            'success' => $success,
            'tags' => (array) $tags,
        ]);
    }

    /**
     * Log a integration (external provider)
     *
     * @param string $event
     * @param \Illuminate\Database\Eloquent\Model|null $entity
     * @param array|null|\AnourValar\HttpClient\Response $data
     * @param bool $success
     * @param array|string|null $tags
     * @return \AnourValar\EloquentJournal\Journal
     */
    public function captureIntegration(string $event, ?Model $entity = null, array|null|Response $data = null, bool $success = true, array|string|null $tags = null): Journal
    {
        if ($data instanceof \AnourValar\HttpClient\Response) {
            $data = $data->dump(true);
        }

        return $this->create([
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getIpAddress(),
            'entity' => $entity ? $this->getEntity($entity) : null,
            'entity_id' => $entity ? $entity->getKey() : null,
            'type' => 'integration',
            'event' => $event,
            'data' => $data,
            'success' => $success,
            'tags' => (array) $tags,
        ]);
    }

    /**
     * Log a metric
     *
     * @param string $event
     * @param \Illuminate\Database\Eloquent\Model|null $entity
     * @param array|null $data
     * @param bool $success
     * @param array|string|null $tags
     * @return \AnourValar\EloquentJournal\Journal
     */
    public function captureMetric(string $event, ?Model $entity = null, ?array $data = null, bool $success = true, array|string|null $tags = null): Journal
    {
        return $this->create([
            'user_id' => $this->getUserId(),
            'ip_address' => $this->getIpAddress(),
            'entity' => $entity ? $this->getEntity($entity) : null,
            'entity_id' => $entity ? $entity->getKey() : null,
            'type' => 'metric',
            'event' => $event,
            'data' => $data,
            'success' => $success,
            'tags' => (array) $tags,
        ]);
    }

    /**
     * Directories
     *
     * @param string $prefix
     * @return array
     */
    public function publishConfig(string $prefix = ''): array
    {
        $onlyPublic = false;
        if (\Auth::user() && ! \Auth::user()->can('admin.administration')) {
            $onlyPublic = true;
        }

        $morphMap = \Illuminate\Database\Eloquent\Relations\Relation::morphMap();
        $entities = [];
        foreach (config('eloquent_journal.entity') as $key => $details) {
            $entities[array_search($key, $morphMap)] = ['title' => trans($details['title'])];
        }

        $events = [];
        foreach (config('eloquent_journal.event') as $key => $details) {
            if ($onlyPublic && empty($details['is_public'])) {
                continue;
            }

            $events[$key] = ['title' => trans($details['title']), 'optgroup' => trans($details['optgroup'])];
        }

        return [
            $prefix . 'entities' => $entities,
            $prefix . 'events' => $events,
        ];
    }

    /**
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return $this->user ? $this->user->getKey() : \Auth::id();
    }

    /**
     * @return string|null
     */
    protected function getIpAddress(): ?string
    {
        if (isset($this->ipAddress)) {
            return $this->ipAddress;
        }

        if (! in_array(\Request::ip(), ['127.0.0.1'])) {
            return \Request::ip();
        }

        return null;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return string
     */
    protected function getEntity(Model $model): string
    {
        $entity = $model->getMorphClass();
        if ($entity == get_class($model)) {
            throw new \RuntimeException('MorphMap must be configured.');
        }

        return $entity;
    }

    /**
     * @param array $data
     * @return \AnourValar\EloquentJournal\Journal
     * @throws \AnourValar\LaravelAtom\Exceptions\InternalValidationException
     */
    protected function create(array $data): Journal
    {
        try {
            $class = config('eloquent_journal.model');
            $journal = tap($class::fields(array_keys($data))->fill($data)->validate(basic: mt_rand(0, 500)))->save();
            event(new \AnourValar\EloquentJournal\Events\JournalCreated($journal));
            return $journal;
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw \AnourValar\LaravelAtom\Exceptions\InternalValidationException::fromValidationException($e);
        }
    }
}
