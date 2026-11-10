<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Page not found | ZEvo</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #f7f4ec;
            color: #24312a;
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
        }
        .card {
            width: 100%;
            max-width: 560px;
            padding: 40px 32px;
            border: 1px solid #e2dccd;
            border-radius: 18px;
            background: #fffdf8;
            box-shadow: 0 18px 50px rgba(36, 49, 42, .08);
            text-align: center;
        }
        .brand { font-size: 22px; font-weight: 800; letter-spacing: .02em; }
        .code { margin: 18px 0 4px; font-size: 64px; line-height: 1; font-weight: 800; color: #1b5e20; }
        h1 { margin: 0 0 10px; font-size: 22px; }
        p { margin: 0 auto 26px; max-width: 420px; color: #5d6b62; line-height: 1.6; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; }
        a {
            display: inline-block;
            padding: 11px 20px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid #1b5e20;
        }
        .primary { background: #1b5e20; color: #fff; }
        .secondary { background: transparent; color: #1b5e20; }
        a:focus-visible { outline: 3px solid #97c49a; outline-offset: 3px; }
        @media (max-width: 420px) { .card { padding: 32px 20px; } .code { font-size: 52px; } }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">ZEvo</div>
        <div class="code" aria-hidden="true">404</div>
        <h1>This page is not here</h1>
        <p>The link may be old, or the product may have moved. Use the shop or the contact page and we will help you find it.</p>
        <div class="actions">
            <a class="primary" href="{{ url('/') }}">Back to shop</a>
            <a class="secondary" href="{{ url('/products') }}">Browse products</a>
            <a class="secondary" href="{{ url('/site/contact-us') }}">Contact us</a>
        </div>
    </main>
</body>
</html>
