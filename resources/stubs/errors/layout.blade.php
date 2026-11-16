<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('code') · {{ config('app.name') }}</title>
        <style>
            :root { color-scheme: light dark; --bg: #fff; --text: #18181b; --muted: #71717a; --line: #e4e4e7; --accent: #f59e0b; }
            @media (prefers-color-scheme: dark) {
                :root { --bg: #27272a; --text: #fff; --muted: #a1a1aa; --line: #3f3f46; }
            }
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--text); font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; line-height: 1.5; }
            main { display: flex; min-height: 100vh; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1.5rem; text-align: center; }
            .brand { margin-bottom: 2.5rem; font-size: 1.25rem; font-weight: 600; color: inherit; text-decoration: none; }
            .code { margin: 0; font-size: 4.5rem; font-weight: 700; line-height: 1; letter-spacing: -0.025em; }
            .bar { width: 3rem; height: 4px; margin: 0.75rem auto 1.5rem; border-radius: 2px; background: var(--accent); }
            .title { margin: 0; font-size: 1.25rem; font-weight: 600; }
            .message { max-width: 28rem; margin: 0.5rem 0 2rem; color: var(--muted); }
            .actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 0.75rem; }
            .actions a { padding: 0.5rem 1rem; border: 1px solid var(--line); border-radius: 0.5rem; color: inherit; text-decoration: none; }
            .actions a.primary { border-color: var(--text); background: var(--text); color: var(--bg); }
            [data-error-details] button { padding: 0.25rem 0.75rem; border: 1px solid var(--line); border-radius: 0.5rem; background: none; color: var(--muted); font: inherit; font-size: 0.875rem; cursor: pointer; }
            [data-error-details] .text-zinc-400 { color: var(--muted); }
            [data-error-details] .grid { display: grid; }
            [data-error-details] .contents { display: contents; }
            [data-error-details] dt, [data-error-details] dd { margin: 0; text-align: left; }
            [data-error-details] dl { grid-template-columns: auto 1fr; gap: 0.25rem 1rem; font-size: 0.75rem; }
            [data-error-details] dd { overflow-wrap: anywhere; color: var(--muted); font-variant-numeric: tabular-nums; }
            [data-error-details] > div:last-of-type { display: flex; justify-content: center; margin-top: 0.75rem; }
            [data-error-details] { width: 100%; max-width: 24rem; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--line); }
        </style>
    </head>
    <body>
        <main>
            <a href="{{ url('/') }}" class="brand">{{ config('app.name') }}</a>

            <h1 class="code">@yield('code')</h1>
            <div class="bar"></div>

            <h2 class="title">@yield('title')</h2>
            <p class="message">@yield('message')</p>

            <div class="actions">
                <a href="javascript:history.back()">{{ __('request-id::errors.back') }}</a>
                <a href="{{ url('/') }}" class="primary">{{ __('request-id::errors.home') }}</a>
            </div>

            <x-request-id::details />
        </main>
    </body>
</html>
