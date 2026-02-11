<?php

namespace AnourValar\EloquentJournal\Http\Controllers\Api;

use Illuminate\Http\Request;

class JournalController extends \Illuminate\Routing\Controller
{
    use \AnourValar\EloquentRequest\ControllerTrait;

    /**
     * Profile
     *
     * @var array
     */
    protected $profile = [
        'filter' => [
            'user_id' => self::PROFILE_FILTER_ID,
            'entity' => self::PROFILE_FILTER_ID,
            'entity_id' => self::PROFILE_FILTER_ID,
            'type' => self::PROFILE_FILTER_ID,
            'event' => self::PROFILE_FILTER_ID,
            'success' => self::PROFILE_FILTER_BOOLEAN,
            'tags' => self::PROFILE_FILTER_JSON,
            'created_at' => self::PROFILE_FILTER_DATE,
        ],

        'relation' => [

        ],

        'scope' => [

        ],

        'sort' => [
            'id',
        ],

        'ranges' => [

        ],

        'options' => [
            \AnourValar\EloquentRequest\Builders\FilterAndScopeBuilder::OPTION_GROUP_RELATION,
            //\AnourValar\EloquentRequest\Builders\Operations\JsonInOperation::OPTION_JSON_PATH_TO_STRUCTURE,

            \AnourValar\EloquentRequest\Actions\CursorPaginateAction::OPTION_APPLY,
        ],

        'default_request' => [
            'per_page' => 20,
            'sort' => ['id' => 'DESC'],
        ],
    ];

    /**
     * Web-service: List of journals
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\CursorPaginator
     */
    public function index(Request $request): \Illuminate\Pagination\CursorPaginator
    {
        $class = config('journal.model');

        return $this->buildBy(
            $class::acl()->heavy()
        );
    }
}
