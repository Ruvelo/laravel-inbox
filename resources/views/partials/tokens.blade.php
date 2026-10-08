{{-- Ruvelo house style tokens, scoped to {{ $scope }} so nothing leaks into the host app. Override any --inbox-* variable to re-theme. --}}
{{ $scope }} {
    --inbox-bg: #ffffff;
    --inbox-subtle: #f8f8fc;
    --inbox-muted: #f0f0f7;
    --inbox-line: #e4e4ef;
    --inbox-ink: #16162a;
    --inbox-text-2: #4b4b63;
    --inbox-text-3: #74748b;
    --inbox-accent: #3d4eff;
    --inbox-accent-2: #a78bfa;
    --inbox-accent-ink: #2b38d6;
    --inbox-accent-soft: #eef0ff;
    --inbox-on-accent: #ffffff;
    --inbox-danger: #e5384f;
    --inbox-danger-soft: #fdecef;
    --inbox-radius: 8px;
    --inbox-radius-lg: 14px;
    --inbox-sans: "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    --inbox-mono: "Geist Mono", ui-monospace, "SF Mono", "Cascadia Code", Menlo, Consolas, monospace;
}
@media (prefers-color-scheme: dark) {
    :root:not([data-theme="light"]):not(.light) {{ $scope }} {
        --inbox-bg: #11111c;
        --inbox-subtle: #171725;
        --inbox-muted: #1f1f30;
        --inbox-line: #2a2a3f;
        --inbox-ink: #f1f1f8;
        --inbox-text-2: #b6b6cc;
        --inbox-text-3: #8787a3;
        --inbox-accent: #8f9bff;
        --inbox-accent-2: #c4b5fd;
        --inbox-accent-ink: #b3bbff;
        --inbox-accent-soft: #1e2150;
        --inbox-on-accent: #0b0b1a;
        --inbox-danger: #ff6b80;
        --inbox-danger-soft: #331520;
    }
}
:root[data-theme="dark"] {{ $scope }}, :root.dark {{ $scope }} {
    --inbox-bg: #11111c;
    --inbox-subtle: #171725;
    --inbox-muted: #1f1f30;
    --inbox-line: #2a2a3f;
    --inbox-ink: #f1f1f8;
    --inbox-text-2: #b6b6cc;
    --inbox-text-3: #8787a3;
    --inbox-accent: #8f9bff;
    --inbox-accent-2: #c4b5fd;
    --inbox-accent-ink: #b3bbff;
    --inbox-accent-soft: #1e2150;
    --inbox-on-accent: #0b0b1a;
    --inbox-danger: #ff6b80;
    --inbox-danger-soft: #331520;
}
