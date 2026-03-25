<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 | <?= htmlspecialchars($title ?? 'Server Error', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f172a; color: #e2e8f0; font-family: Inter, Arial, sans-serif; }
        main { width: min(620px, calc(100vw - 32px)); padding: 36px; border-radius: 24px; background: rgba(15, 23, 42, 0.9); border: 1px solid rgba(148, 163, 184, 0.2); box-shadow: 0 20px 60px rgba(2, 6, 23, 0.5); }
        .code { font-size: 0.8rem; letter-spacing: 0.22em; text-transform: uppercase; color: #38bdf8; }
        h1 { margin: 12px 0 10px; font-size: clamp(2rem, 5vw, 3rem); }
        p { line-height: 1.6; color: #cbd5e1; }
        a { display: inline-flex; margin-top: 18px; padding: 12px 18px; border-radius: 999px; background: #38bdf8; color: #082f49; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <main>
        <div class="code">500 Error</div>
        <h1>Something went wrong</h1>
        <p><?= htmlspecialchars($message ?? 'Something went wrong while processing your request.', ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars($actionHref ?? '/login', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($actionLabel ?? 'Sign in', ENT_QUOTES, 'UTF-8') ?></a>
    </main>
</body>
</html>
