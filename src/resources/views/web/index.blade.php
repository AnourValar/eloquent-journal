@inject('dateHelper', AnourValar\LaravelAtom\Helpers\DateHelper::class)
@extends('admin.layout')

@section('title')
  @lang('Журнал')
@endsection

@section('content')
  @include('journal::web.index.filters')

  <x-form::table-page :request="$request" :collection="$journals">
    <table class="table table-bordered table-striped table-empty">
      <thead>
        <tr>
          <th style="width: 100px;"># <x-form::sort :route="Route::currentRouteName()" :request="$request" attribute="id" /></th>
          <th style="width: 150px;">@lang('Тип')</th>
          <th style="width: 400px;">@lang('Событие')</th>
          <th style="width: 270px;">@lang('Сущность')</th>
          <th style="width: 290px;">@lang('Теги')</th>
          <th>@lang('Описание')</th>
          <th class="text-center" style="width: 80px;">@lang('Детали')</th>
          <th style="width: 310px;">@lang('Инициатор')</th>
          <th style="width: 120px;">@lang('IP адрес')</th>
          <th style="width: 160px;">@lang('Дата')</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($journals as $journal)
          <tr @class(['table-danger' => ! $journal->success])>
            <td>{{ $journal->id }}</td>
            <td>{{ $journal->type_title }}</td>
            <td>{{ $journal->event_title }}</td>
            <td>
              @if ($journal->entity)
                {{ $journal->entity_title }} <span class="text-muted">[#{{ $journal->entity_id }}]</span>
              @endif
            </td>
            <td><x-form::str_limit :string="implode(', ', $journal->tags ?? [])" :limit="30" /></td>
            <td>{{ $journal->short_description }}</td>
            <td class="text-center">
              @if ($description = $journal->full_description)
                <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#journal-{{ $journal->id }}">&nbsp;<i class="fa-solid fa-pen"></i>&nbsp;</button>
                @push('modals')
                  @include('journal::web.index.modal', ['id' => $journal->id, 'description' => $description])
                @endpush
              @endif
            </td>
            <td>
              {{ $journal->user?->title }}
              @if ($journal->user)
                <span class="text-muted">[#{{ $journal->user->id }}]</span>
              @endif
            </td>
            <td>{{ $journal->ip_address }}</td>
            <td>{{ $dateHelper->formatDateTime($journal->created_at, config('app.timezone_client')) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </x-form::table-page>

  @stack('modals')
@endsection
