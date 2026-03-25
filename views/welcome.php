<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            color-scheme: light dark;
            font-family: Arial, sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f5f7fb;
            color: #0f172a;
        }

        main {
            max-width: 720px;
            padding: 32px;
            background: #ffffff;
            border: 1px solid #dbe2ea;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        code {
            background: #eef2ff;
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></h1>
        <p>Phase 1 scaffold is active.</p>
        <p>Try <code>/api/ping</code> for a JSON response.</p>
    </main>
</body>
</html>
