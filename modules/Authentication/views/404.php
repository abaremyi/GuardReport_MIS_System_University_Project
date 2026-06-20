<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — GuardReport</title>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@700;800&family=DM+Sans:opsz,wght@9..40,400;9..40,500&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      display: flex; align-items: center; justify-content: center;
      min-height: 100vh; background: #f0f4f8; color: #0f172a;
    }
    .box { text-align: center; padding: 32px; max-width: 420px; }
    .box .icon { font-size: 72px; margin-bottom: 16px; }
    .box h1 {
      font-family: 'Sora', sans-serif; font-size: 72px; font-weight: 800;
      color: #0F2744; margin-bottom: 8px; line-height: 1;
    }
    .box h2 { font-size: 20px; font-weight: 600; margin-bottom: 10px; }
    .box p  { font-size: 14px; color: #64748b; margin-bottom: 28px; }
    .box a  {
      display: inline-flex; align-items: center; gap: 8px;
      background: #0F2744; color: #fff; padding: 10px 24px;
      border-radius: 99px; text-decoration: none; font-weight: 600; font-size: 14px;
      transition: background .15s;
    }
    .box a:hover { background: #1E3A5F; }
  </style>
</head>
<body>
  <div class="box">
    <div class="icon">🛡️</div>
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>The page you're looking for doesn't exist or you don't have permission to view it.</p>
    <a href="<?= defined('BASE_URL') ? BASE_URL . '/admin/dashboard' : '/admin/dashboard' ?>">
      ← Back to Dashboard
    </a>
  </div>
</body>
</html>