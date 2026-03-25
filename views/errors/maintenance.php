<?php $appName = (string) ($GLOBALS['app']['name'] ?? 'Catarman Animal Shelter'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: linear-gradient(180deg, #eff6ff, #e2e8f0); color: #0f172a; font-family: Inter, Arial, sans-serif; }
        main { width: min(620px, calc(100vw - 32px)); padding: 36px; border-radius: 24px; background: rgba(255, 255, 255, 0.92); border: 1px solid #cbd5e1; box-shadow: 0 20px 60px rgba(15, 23, 42, 0.12); }
        .code { font-size: 0.8rem; letter-spacing: 0.22em; text-transform: uppercase; color: #2563eb; }
        h1 { margin: 12px 0 10px; font-size: clamp(2rem, 5vw, 3rem); }
        p { line-height: 1.6; color: #475569; }
    </style>
</head>
<body>
    <main>
        <div class="code">Maintenance Mode</div>
        <h1>We’ll be back shortly</h1>
        <p><?= htmlspecialchars($message ?? 'The system is temporarily unavailable while maintenance is in progress.', ENT_QUOTES, 'UTF-8') ?></p>
    </main>
</body>
</html>
