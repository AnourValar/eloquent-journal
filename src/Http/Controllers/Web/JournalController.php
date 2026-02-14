<?php

namespace AnourValar\EloquentJournal\Http\Controllers\Web;

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
     * Interface: List of users
     *
     * @param \Illuminate\Http\Request $request
     * @param \AnourValar\EloquentJournal\Service $journalService
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request, \AnourValar\EloquentJournal\Service $journalService)
    {
        $class = config('eloquent_journal.model');

        $journals = $this->buildBy(
            $class::acl()->heavy()
        );

        $request = $this->getBuildRequest();
        $request['configs'] = $journalService->publishConfig();

        return view('eloquent_journal::web.index', compact('journals', 'request'));
    }
}
