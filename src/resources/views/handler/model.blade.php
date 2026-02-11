@php
  $isList = (is_array($data['old'] ?? null) && array_is_list($data['old'])) || (is_array($data['new'] ?? null) && array_is_list($data['new']));

  $attributes = array_unique(array_merge(
    array_keys(is_array($data['old'] ?? null) ? $data['old'] : []),
    array_keys(is_array($data['new'] ?? null) ? $data['new'] : [])
  ));
@endphp

<table class="table table-sm  mb-0 table-striped table-bordered journal-model">
    @foreach ($attributes as $key)
      @php
        $old = $data['old'][$key] ?? null;
        $new = $data['new'][$key] ?? null;
      @endphp
      <tr>
        @if (! $isList)
          <td @class(['table-success' => isset($new) && ! isset($old), 'table-danger' => ! isset($new) && isset($old)]) style="width: 300px;">{{ $key }}</td>
        @endif
        <td>
            @if (is_array($old) || is_array($new))
              @include('journal::handler.model', ['data' => ['old' => $old, 'new' => $new]])
            @elseif ($old === $new)
              {{ $new }}
            @else
              <span style="background-color: #ffe7e7; text-decoration: line-through;">{{ $old }}</span>
              <span style="background-color: #ddfade;">{{ $new }}</span>
            @endif
        </td>
      </tr>
    @endforeach
</table>
