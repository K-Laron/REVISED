<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 | <?= htmlspecialchars($title ?? 'Forbidden', ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; font-family: Inter, Arial, sans-serif; }
        main { width: min(560px, calc(100vw - 32px)); padding: 32px; border-radius: 20px; background: #fff; border: 1px solid #e2e8f0; box-shadow: 0 18px 48px rgba(15, 23, 42, 0.08); }
        .code { font-size: 0.8rem; letter-spacing: 0.22em; text-transform: uppercase; color: #b45309; }
        h1 { margin: 12px 0 10px; font-size: clamp(2rem, 5vw, 3rem); }
        p { line-height: 1.6; color: #475569; }
        a { display: inline-flex; margin-top: 18px; padding: 12px 18px; border-radius: 999px; background: #0f172a; color: #fff; text-decoration: none; }
    </style>
</head>
<body>
    <main>
        <div class="code">403 Error</div>
        <h1>Access denied</h1>
        <p><?= htmlspecialchars($message ?? 'Your account does not have permission to open this page.', ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars($actionHref ?? '/login', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($actionLabel ?? 'Sign in', ENT_QUOTES, 'UTF-8') ?></a>
    </main>
</body>
</html>
