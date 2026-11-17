@extends('layouts.app')

@section('title', 'ボールカレンダー')

@section('content')
<div class="ball-calendar-page">
    @if ($calendar['rows'] === [])
        <div class="ball-cal-empty">
            <p>開いているボールはありません。</p>
        </div>
    @else
        <div class="ball-cal-scroll" data-ball-calendar>
            <table class="ball-cal">
                <colgroup>
                    <col class="cal-col-who">
                    <col class="cal-col-topic">
                    @foreach ($calendar['dates'] as $date)
                        <col class="cal-col-day">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        <th class="cal-who" scope="col">担当</th>
                        <th class="cal-topic" scope="col">対象の議題</th>
                        @foreach ($calendar['dates'] as $date)
                            <th @class([
                                'cal-head',
                                'is-weekend' => $date['weekend'],
                                'is-today' => $date['today'],
                                'is-month' => $date['month_start'],
                            ]) scope="col">
                                <span class="cal-md">{{ $date['label'] }}</span>
                                <span class="cal-wd">{{ $date['today'] ? '今日' : $date['weekday'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($calendar['rows'] as $row)
                        <tr class="cal-row" data-href="{{ route('projects.show', $row['project_id']) }}" tabindex="0" title="{{ $row['task'] }}">
                            <th class="cal-who" scope="row" title="{{ $row['who'] }}">{{ $row['who'] }}</th>
                            <td class="cal-topic" title="{{ $row['project_name'] }}">
                                <a href="{{ route('projects.show', $row['project_id']) }}">{{ $row['project_name'] }}</a>
                            </td>
                            @foreach ($row['cells'] as $cell)
                                @if ($cell['type'] === 'bar')
                                    <td class="cal-bar-cell" colspan="{{ $cell['span'] }}">
                                        <span class="cal-bar">{{ $cell['task'] }}</span>
                                    </td>
                                @else
                                    <td @class([
                                        'cal-day',
                                        'is-weekend' => $cell['weekend'],
                                        'is-today' => $cell['today'],
                                        'is-month' => $cell['month_start'],
                                    ])></td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@if ($calendar['rows'] !== [])
    @push('scripts')
        <script src="{{ asset('js/ball-calendar.js') }}?v={{ filemtime(public_path('js/ball-calendar.js')) }}"></script>
    @endpush
@endif
