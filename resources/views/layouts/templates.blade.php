@php
    /*
     * Shared shell for the new landing designs.
     *
     * The template view yields only its own sections. This layout owns the
     * document, the event layer, the app-name header, the one system order
     * form and the new landing assets. It never renders a footer.
     */
    $tpl = $tpl ?? \App\Support\LandingTemplateVars::make(
        $landingPage ?? null,
        isset($campaign) && $campaign ? $campaign->template : null,
        $campaign ?? null
    );

    $templateKey = $tpl->templateKey();
    $assets = $tpl->assets();
    $layoutCssPath = public_path('frontend/landing/templates-layout.css');
    $layoutCssVersion = file_exists($layoutCssPath) ? filemtime($layoutCssPath) : time();
    $interactionsPath = public_path('frontend/landing/landing-interactions.js');
    $interactionsVersion = file_exists($interactionsPath) ? filemtime($interactionsPath) : time();
    $favicon = $tpl->text('favicon');
@endphp
<!DOCTYPE html>
<html lang="{{ $tpl->text('lang', 'bn') }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $tpl->text('page_title', $tpl->productName()) }}</title>
    <meta name="description" content="{{ $tpl->text('meta_description') }}">
    @if($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif

    @include('frontend.partials.event-layer-head')

    @foreach($assets['css'] as $css)
        @if(preg_match('~^https?://~i', $css))
            <link rel="stylesheet" href="{{ $css }}">
        @else
            @php $localCss = public_path($css); @endphp
            <link rel="stylesheet" href="{{ asset($css) }}?v={{ file_exists($localCss) ? filemtime($localCss) : time() }}">
        @endif
    @endforeach
    <link rel="stylesheet" href="{{ asset('frontend/landing/templates-layout.css') }}?v={{ $layoutCssVersion }}">

    @foreach($assets['head_js'] as $js)
        @if(preg_match('~^https?://~i', $js))
            <script src="{{ $js }}"></script>
        @else
            <script src="{{ asset($js) }}"></script>
        @endif
    @endforeach
</head>
<body class="zlt-body {{ $tpl->bodyClass() }}" data-landing-template="{{ $templateKey }}">
    @include('frontend.partials.event-layer-body')

    @include('frontend.landing.partials.app-header', ['tpl' => $tpl])

    @yield('content')

    @include('frontend.landing.partials.order-section', ['tpl' => $tpl])

    @stack('scripts')

    @foreach($assets['body_js'] as $js)
        @if(preg_match('~^https?://~i', $js))
            <script src="{{ $js }}"></script>
        @else
            <script src="{{ asset($js) }}"></script>
        @endif
    @endforeach

    <script src="{{ asset('frontend/landing/landing-interactions.js') }}?v={{ $interactionsVersion }}"></script>
</body>
</html>
