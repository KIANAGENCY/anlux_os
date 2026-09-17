@if(!empty($anluxLogoUrl))
    <link rel="icon" href="{{ $anluxLogoUrl }}">
    <link rel="apple-touch-icon" href="{{ $anluxLogoUrl }}">
@endif
@if(!empty($anluxBrandCss))
    <style id="anlux-runtime-brand">
        {!! $anluxBrandCss !!}
        :root {
            --primary: var(--anlux-primary);
            --primary-dark: var(--anlux-primary-hover);
            --deep: var(--anlux-deep);
            --bg: var(--anlux-canvas);
            --text: var(--anlux-text);
        }
        html, body { font-family: var(--anlux-font-family) !important; }
        .anlux-guest-shell {
            background:
                radial-gradient(circle at top left, color-mix(in srgb, var(--anlux-primary) 48%, transparent), transparent 40%),
                radial-gradient(circle at bottom right, color-mix(in srgb, var(--anlux-accent) 42%, transparent), transparent 35%),
                linear-gradient(160deg, var(--anlux-hero-from) 0%, var(--anlux-hero-via) 45%, var(--anlux-hero-to) 100%);
        }
    </style>
@endif
