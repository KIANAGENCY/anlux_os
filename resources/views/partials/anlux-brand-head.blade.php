@if(!empty($anluxLogoUrl))
    <link rel="icon" href="{{ $anluxLogoUrl }}">
    <link rel="apple-touch-icon" href="{{ $anluxLogoUrl }}">
@endif
@if(!empty($anluxBrandCss))
    <style id="anlux-runtime-brand">
        {!! $anluxBrandCss !!}
        :root {
            --primary: var(--anlux-primary);
            --primary-dark: var(--anlux-deep);
            --bg: var(--anlux-canvas);
            --text: var(--anlux-text);
        }
        html, body { font-family: var(--anlux-font-family) !important; }
    </style>
@endif
