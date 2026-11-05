@extends('layouts.app', [
    'title' => $title,
    'description' => $intro ?? null,
    'schema' => [...($schema ?? []), 'breadcrumbs' => [[$title, url()->current()]]],
])

@section('content')
    <section class="page-head">
        <div class="wrap">
            <h1>{{ $title }}</h1>
            @if (filled($intro ?? null))
                <p class="lead-text">{{ $intro }}</p>
            @endif
        </div>
    </section>
    <section class="page-body">
        <div class="wrap">
            <div class="prose">{!! $html !!}</div>
        </div>
    </section>
@endsection
