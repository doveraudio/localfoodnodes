@extends('admin.layout')

@section('title', join(array_keys($breadcrumbs), ' - '))

@section('content')
    @include('admin.page-header')

    <div class="card">
        <div class="card-header">{{ trans('admin/event.guests_in') }} {{ $event->name }}</div>
        <div class="card-block">
            @if ($event->userLinks()->count() > 0)
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/event.name') }}</th>
                            <th>{{ trans('admin/event.address') }}</th>
                            <th>{{ trans('admin/event.zip') }}</th>
                            <th>{{ trans('admin/event.city') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($event->userLinks() as $userLink)
                            <tr>
                                <td>{{ $userLink->getUser()->name }}</td>
                                <td>{{ $userLink->getUser()->address }}</td>
                                <td>{{ $userLink->getUser()->zip }}</td>
                                <td>{{ $userLink->getUser()->city }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                {{ trans('admin/event.no_users') }}
            @endif
        </div>
    </div>
@endsection
