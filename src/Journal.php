<?php

namespace AnourValar\EloquentJournal;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Relations\Relation;

class Journal extends Model
{
    use \AnourValar\EloquentValidation\ModelTrait;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Illuminate\Database\Eloquent\MassPrunable;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Trim columns
     *
     * @var array
     */
    protected $trim = [
        'ip_address', 'entity', 'type', 'event', 'data', 'tags',
    ];

    /**
     * '',[] => null convert
     *
     * @var array
     */
    protected $nullable = [
        'user_id', 'ip_address', 'entity', 'entity_id', 'data', 'tags',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'updated_at',
    ];

    /**
     * The model's attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [

    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'ip_address' => 'string',
        'entity' => 'string',
        'entity_id' => 'integer',
        'type' => 'string',
        'event' => 'string',
        'data' => 'json:unicode',
        'success' => 'boolean',
        'tags' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Mutators for nested JSON.
     * jsonb - sort an array by key
     * nullable - '',[] => null convert (nested)
     * purges - remove null elements (nested)
     * types - set the type of value (nested)
     * sorts - sort an array (nested)
     * lists - drop array keys (nested)
     *
     * @var array
     */
    protected $jsonNested = [
        'data' => [
            'jsonb' => true,
            'nullable' => [],
            'purges' => [],
            'types' => [],
            'sorts' => [],
            'lists' => [],
        ],

        'tags' => [
            'jsonb' => true,
            'nullable' => ['*'],
            'purges' => ['*'],
            'types' => ['$.*' => 'string'],
            'sorts' => [],
            'lists' => ['$'],
        ],
    ];

    /**
     * Calculated columns
     *
     * @var array
     */
    protected $computed = [

    ];

    /**
     * Immutable columns
     *
     * @var array
     */
    protected $unchangeable = [
        'user_id', 'ip_address', 'entity', 'entity_id', 'type', 'event', 'data', 'success',
    ];

    /**
     * Unique columns sets
     *
     * @var array
     */
    protected $unique = [

    ];

    /**
     * @see \AnourValar\EloquentValidation\ModelTrait::getAttributeNamesFromModelLang()
     *
     * @return array
     */
    protected function getAttributeNamesFromModelLang(): array
    {
        $attributeNames = trans('journal::journal.attributes');

        return is_array($attributeNames) ? $attributeNames : [];
    }

    /**
     * @var string
     */
    protected static $factory = \AnourValar\EloquentJournal\resources\database\factories\JournalFactory::class;

    /**
     * Get the validation rules
     *
     * @return array
     */
    public function saveRules()
    {
        $entities = implode(',', array_keys(Relation::morphMap()));

        return [
            'user_id' => ['nullable', 'integer', 'min:1'],
            'ip_address' => ['nullable', 'ip'],
            'entity' => ['nullable', 'required_with:entity_id', 'string', "in:{$entities}"],
            'entity_id' => ['nullable', 'required_with:entity', 'integer', 'min:1'],
            'type' => ['required', 'string', 'config:journal.type'],
            'event' => ['required', 'string', 'config:journal.event'],
            'data' => ['nullable', 'array'],
            'success' => ['required', 'boolean'],
            'tags' => ['nullable', 'array', 'min:1', 'max:10'],
                'tags.*' => ['required', 'string', 'min:1', 'max:100'],
        ];
    }

    /**
     * "Save" after-validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param bool $basic
     * @return void
     */
    public function saveAfterValidation(\Illuminate\Validation\Validator $validator, bool $basic): void
    {
        // user_id
        if (! $basic && $this->isDirty('user_id') && $this->user_id) {
            $class = config('auth.providers.users.model');
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($class))) {
                $user = $class::withTrashed()->find($this->user_id);
            } else {
                $user = $class::find($this->user_id);
            }

            if (! $user) {
                $validator->errors()->add('user_id', trans('journal::journal.user_id_not_exists'));
            }
        }

        // entity_id
        if (! $basic && $this->isDirty('entity_id') && $this->entity_id) {
            $class = Relation::morphMap()[$this->entity];
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($class))) {
                $entity = $class::withTrashed()->find($this->entity_id);
            } else {
                $entity = $class::find($this->entity_id);
            }

            if (! $entity) {
                $validator->errors()->add('entity_id', trans('journal::journal.entity_id_not_exists'));
            }
        }

        // ...
        if ($this->isDirty('entity', 'entity_id', 'type', 'event', 'data')) {
            $this->getTypeHandler()->validate($validator);
        }
    }

    /**
     * "Delete" after-validation
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param bool $basic
     * @return void
     */
    public function deleteAfterValidation(\Illuminate\Validation\Validator $validator, bool $basic): void
    {

    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        $class = config('auth.providers.users.model');

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses($class))) {
            return $this->belongsTo($class)->withTrashed();
        }

        return $this->belongsTo($class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function entitable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo(null, 'entity', 'entity_id')->withTrashed();
    }

    /**
     * Heavy columns
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeHeavy(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder
            ->with([
                'user' => fn ($query) => $query->light(),
                'entitable' => fn ($query) => $query->light(),
            ])
            ->select(['id', 'user_id', 'ip_address', 'entity', 'entity_id', 'type', 'event', 'data', 'success', 'tags', 'created_at'])
            ->addPublishFields([
                'id', 'ip_address', 'entity', 'type', 'event', 'success', 'tags', 'created_at',
                'entity_title', 'type_title', 'event_title', 'short_description', 'full_description', 'user', 'entitable',
            ]);
    }

    /**
     * ACL
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Foundation\Auth\User|null $user
     * @return void
     */
    public function scopeAcl(\Illuminate\Database\Eloquent\Builder $builder, ?\Illuminate\Foundation\Auth\User $user = null): void
    {
        if (! $user) {
            $user = \Auth::user();
        }

        if (! $user->can('admin.administration')) {
            $builder
                ->where('user_id', '=', $user->id)
                ->whereIn('event', array_keys(array_filter(config('journal.event'), fn ($item) => ! empty($item['is_public']))));
        }
    }

    /**
     * @return \AnourValar\EloquentJournal\Handlers\TypeInterface
     */
    public function getTypeHandler(): \AnourValar\EloquentJournal\Handlers\TypeInterface
    {
        return \App::make(config('journal.type')[$this->type]['bind']);
    }

    /**
     * Virtual attribute: type_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function typeDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("journal.type.{$this->type}"),
        );
    }

    /**
     * Virtual attribute: type_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function typeTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trans(config("journal.type.{$this->type}.title")),
        );
    }

    /**
     * Virtual attribute: entity_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function entityDetails(): Attribute
    {
        $morphMap = Relation::morphMap();
        return Attribute::make(
            get: fn ($value) => $this->entity ? config("journal.entity.{$morphMap[$this->entity]}") : null,
        );
    }

    /**
     * Virtual attribute: entity_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function entityTitle(): Attribute
    {
        $morphMap = Relation::morphMap();
        return Attribute::make(
            get: fn ($value) => $this->entity ? trans(config("journal.entity.{$morphMap[$this->entity]}.title")) : null,
        );
    }

    /**
     * Virtual attribute: entity_class
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function entityClass(): Attribute
    {
        $morphMap = Relation::morphMap();
        return Attribute::make(
            get: fn ($value) => $this->entity ? $morphMap[$this->entity] : null,
        );
    }

    /**
     * Virtual attribute: event_details
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function eventDetails(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => config("journal.event.{$this->event}"),
        );
    }

    /**
     * Virtual attribute: event_title
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function eventTitle(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => trans(config("journal.event.{$this->event}.title")),
        );
    }

    /**
     * Virtual attribute: short_description
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function shortDescription(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->getTypeHandler()->shortDescription($this),
        );
    }

    /**
     * Virtual attribute: full_description
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function fullDescription(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->getTypeHandler()->fullDescription($this),
        );
    }

    /**
     * Get the prunable model query.
     * @see \Illuminate\Database\Eloquent\MassPrunable
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subMonths(12));
    }
}
