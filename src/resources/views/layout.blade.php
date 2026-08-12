@php
  \Illuminate\Pagination\Paginator::useBootstrap();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <title>@yield('title')</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.7.2/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" />

  <style>
    :root {
      --jr-bg: #f7f8fa;
      --jr-border: #e5e7eb;
      --jr-line: #eef0f4;
      --jr-muted: #6b7280;
    }

    body {
      background-color: var(--jr-bg);
      color: #111827;
      font-size: .875rem;
    }

    /* Header */
    .jr-header {
      background-color: #1a202c;
      padding: .875rem 0;
      margin-bottom: 1.5rem;
    }
    .jr-brand {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      color: #fff;
      font-size: 1.0625rem;
      font-weight: 600;
      text-decoration: none;
    }
    .jr-brand i {
      color: #94a3b8;
    }

    /* Cards */
    .card {
      border: 1px solid var(--jr-border);
      border-radius: .5rem;
      box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
    }
    .card + .card {
      margin-top: 1rem;
    }
    .card-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      padding: .75rem 1rem;
      background-color: #fff;
      border-bottom: 1px solid var(--jr-border);
      border-radius: .5rem .5rem 0 0;
    }
    .card-header[data-card-widget] {
      cursor: pointer;
      user-select: none;
    }
    .card-title {
      margin: 0;
      font-size: .9375rem;
      font-weight: 600;
    }
    .card-body {
      padding: 1rem;
    }
    .card-footer {
      padding: .75rem 1rem;
      background-color: #fbfcfd;
      border-top: 1px solid var(--jr-border);
      border-radius: 0 0 .5rem .5rem;
    }
    .card-tools {
      display: flex;
      gap: .25rem;
    }
    .btn-tool {
      padding: .125rem .375rem;
      background-color: transparent;
      border: 0;
      color: var(--jr-muted);
    }
    .btn-tool:hover {
      color: #111827;
    }
    .collapsed-card > .card-body,
    .collapsed-card > .card-footer {
      display: none;
    }

    /* Table */
    .table-page > .card-body {
      padding: 0;
      overflow-x: auto;
    }
    .table-page > .card-body::after {
      content: '';
      display: block;
      clear: both;
    }
    .table-page .table {
      margin-bottom: 0;
      --bs-table-striped-bg: #fafbfc;
      --bs-table-hover-bg: #f5f8ff;
    }
    .table-page .table > thead > tr > th {
      padding: .5rem .75rem;
      background-color: #fbfcfd;
      color: var(--jr-muted);
      font-size: .72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
      white-space: nowrap;
    }
    .table-page .table > tbody > tr > td {
      padding: .5rem .75rem;
      vertical-align: middle;
    }
    .table > :not(caption) > * > * {
      border-color: var(--jr-line);
    }
    .table-bordered > :not(caption) > * > * {
      border-width: 0 0 1px;
    }

    /* Modal */
    .modal-xxl {
      max-width: calc(100vw - 4rem);
    }
    .journal-metric,
    .journal-integration {
      margin: 0;
      white-space: pre-wrap;
      word-break: break-word;
    }
    .journal-model {
      word-break: break-word;
    }

    /* Bootstrap 4 compatibility */
    .float-left {
      float: left !important;
    }
    .float-right {
      float: right !important;
    }
    .text-right {
      text-align: right !important;
    }
    .pl-3 {
      padding-left: 1rem !important;
    }
    .pr-3 {
      padding-right: 1rem !important;
    }
    .ml-2 {
      margin-left: .5rem !important;
    }
    .mr-2 {
      margin-right: .5rem !important;
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group > label {
      margin-bottom: .25rem;
      color: #374151;
      font-size: .8125rem;
      font-weight: 500;
    }
    .badge-primary {
      background-color: var(--bs-primary);
      color: #fff;
    }
    .btn-default {
      background-color: #fff;
      border-color: #d1d5db;
      color: #374151;
    }
    .btn-default:hover {
      background-color: #f3f4f6;
    }
    .close {
      padding: 0;
      background-color: transparent;
      border: 0;
      color: #9ca3af;
      font-size: 1.5rem;
      line-height: 1;
    }
    .close:hover {
      color: #111827;
    }
  </style>
</head>

<body>
  <header class="jr-header">
    <div class="container-fluid px-4">
      <span class="jr-brand">
        <i class="fa-solid fa-clock-rotate-left"></i>@yield('title')
      </span>
    </div>
  </header>

  <main class="container-fluid px-4 pb-4">
    @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>

  @if (app()->getLocale() != 'en')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/{{ app()->getLocale() }}.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/{{ app()->getLocale() }}.js"></script>
  @endif

  <script>
    $(function () {
      // Bootstrap 4 attributes => Bootstrap 5
      $('[data-toggle], [data-target], [data-dismiss]').each(function () {
        var element = $(this);

        ['toggle', 'target', 'dismiss'].forEach(function (name) {
          var value = element.attr('data-' + name);

          if (value !== undefined && element.attr('data-bs-' + name) === undefined) {
            element.attr('data-bs-' + name, value);
          }
        });
      });

      // Tooltips
      $('[data-bs-toggle="tooltip"]').each(function () {
        new bootstrap.Tooltip(this);
      });

      // Card: collapse
      $(document).on('click', '[data-card-widget="collapse"]', function (event) {
        event.preventDefault();
        event.stopPropagation();

        $(this).closest('.card').toggleClass('collapsed-card')
          .find('[data-card-widget="collapse"] i').toggleClass('fa-plus fa-minus');
      });

      // Select2 (data-placeholder, data-tags are handled by select2 itself)
      $('.select2bs4').select2({
        theme: 'bootstrap-5',
        width: '100%',
        allowClear: true,
        language: @json(app()->getLocale()),
      });

      // Datetime picker
      flatpickr('.datetime-picker', {
        enableTime: true,
        time_24hr: true,
        allowInput: true,
        dateFormat: @json(trans('laravel_atom::formats.datetime_format')),
        locale: (flatpickr.l10ns[@json(app()->getLocale())] || flatpickr.l10ns.default),
      });

      // Empty table
      $('table.table-empty > tbody:empty')
        .append('<tr><td colspan="100" class="text-center text-muted p-4">' + @json(__('Ничего не найдено')) + '</td></tr>');

      // Filters: reset
      $('.filters-reset').on('click', function () {
        $(this).closest('form').find('[name^="filter"], [name^="relation"], [name^="scope"]').val(null).trigger('change');
      });

      // Filters: empty values are not a filter
      $('form').on('submit', function () {
        $(this).find('.disable-if-empty').each(function () {
          if (! $(this).val()) {
            $(this).prop('disabled', true);
          }
        });
      });
    });
  </script>
</body>
</html>
