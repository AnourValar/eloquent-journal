@inject('dateHelper', AnourValar\LaravelAtom\Helpers\DateHelper::class)
<x-form::filters :request="$request" :route="Route::currentRouteName()">
  <div class="row">
    <div class="col-3">
      <div class="form-group">
        <label>@lang('Событие')</label>
        <x-select
          name="filter[event][in][]"
          :options="$request['configs']['events']"
          :selected="$request->filter('event.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Сущность')</label>
        <x-select
          name="filter[entity][in][]"
          :options="$request['configs']['entities']"
          :selected="$request->filter('entity.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('ID сущности')</label>
        <x-input
          type="text"
          name="filter[entity_id][=]"
          :value="$request->filter('entity_id.=')"
          class="form-control disable-if-empty"
          autocomplete="off"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Успешность')</label>
        <x-select
          name="filter[success][in][]"
          :options="[1 => __('Да'), 0 => ('Нет')]"
          :selected="$request->filter('success.in', [])"
          class="select2bs4"
          multiple="multiple"
          data-placeholder="{{ __('Не выбрано') }}"
        />
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-3">
      <div class="form-group">
        <label>@lang('ID инициатора')</label>
        <x-input
          type="text"
          name="filter[user_id][=]"
          :value="$request->filter('user_id.=')"
          class="form-control disable-if-empty"
          autocomplete="off"
        />
      </div>
    </div>

    <div class="col-3">
      <div class="form-group">
        <label>@lang('Теги')</label>
        <x-select
          name="filter[tags][json-contains][]"
          multiple="multiple"
          :options="$request->filter('tags.json-contains') ? array_combine($request->filter('tags.json-contains'), $request->filter('tags.json-contains')) : []"
          :selected="$request->filter('tags.json-contains')"
          class="select2bs4"
          data-tags="true"
        />
      </div>
    </div>

    <div class="col-2">
      <div class="form-group">
        <label>@lang('Создан, с')</label>
        <x-input
          type="text"
          name="filter[created_at][>=]"
          :value="$dateHelper->formatDateTime($request->filter('created_at.>='), config('app.timezone_client'))"
          class="form-control datetime-picker disable-if-empty"
          autocomplete="off"
        />
      </div>
    </div>

    <div class="col-2">
      <div class="form-group">
        <label>@lang('Создан, по')</label>
        <x-input
          type="text"
          name="filter[created_at][<=]"
          :value="$dateHelper->formatDateTime($request->filter('created_at.<='), config('app.timezone_client'))"
          class="form-control datetime-picker disable-if-empty"
          autocomplete="off"
        />
      </div>
    </div>
  </div>
</x-form::filters>
