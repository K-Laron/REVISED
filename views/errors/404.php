<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 | <?= htmlspecialchars($title ?? 'Not Found', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4f7fb; color: #0f172a; font-family: Inter, Arial, sans-serif; }
        main { width: min(560px, calc(100vw - 32px)); padding: 32px; border-radius: 20px; background: #fff; border: 1px solid #dbe2ea; box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08); }
        .code { font-size: 0.8rem; letter-spacing: 0.22em; text-transform: uppercase; color: #64748b; }
        h1 { margin: 12px 0 10px; font-size: clamp(2rem, 5vw, 3rem); }
        p { line-height: 1.6; color: #475569; }
        a { display: inline-flex; margin-top: 18px; padding: 12px 18px; border-radius: 999px; background: #2563eb; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <div class="code">404 Error</div>
        <h1>Page not found</h1>
        <p><?= htmlspecialchars($message ?? 'The page you requested does not exist or may have been moved.', ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars($actionHref ?? '/login', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($actionLabel ?? 'Sign in', ENT_QUOTES, 'UTF-8') ?></a>
    </main>
</body>
</html>
