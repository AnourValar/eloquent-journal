<?php

namespace AnourValar\EloquentJournal\Tests\Models;

/**
 * @property int $id
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $status
 * @property array|null $settings
 * @property int|null $balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class User extends \Illuminate\Foundation\Auth\User
{
    use \AnourValar\EloquentValidation\ModelTrait;
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    use \Illuminate\Database\Eloquent\SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>
     */
    protected $guarded = [];

    /**
     * Trim columns
     *
     * @var array
     */
    protected $trim = [
        'email', 'phone', 'status',
    ];

    /**
     * '',[] => null convert
     *
     * @var array
     */
    protected $nullable = [
        'email', 'phone', 'status', 'settings', 'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'email' => 'string',
        'phone' => 'string',
        'status' => 'string',
        'settings' => 'json',
        'balance' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
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

    ];

    /**
     * Unique columns sets
     *
     * @var array
     */
    protected $unique = [

    ];

    /**
     * @var string
     */
    protected static $factory = \AnourValar\EloquentJournal\Tests\Factories\UserFactory::class;

    /**
     * Light columns
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @return void
     */
    public function scopeLight(\Illuminate\Database\Eloquent\Builder $builder): void
    {
        $builder->select(['id', 'email']);
    }
}
