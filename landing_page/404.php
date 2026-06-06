<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="robots" content="noindex, follow">
<title>Page Not Found — Binest</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;color:#111827;background:#F9FAFB;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.err-box{text-align:center;max-width:420px}
.err-box img{width:80px;height:80px;border-radius:20px;margin-bottom:24px}
.err-box h1{font-size:72px;font-weight:900;color:#E5E7EB;line-height:1;margin-bottom:8px}
.err-box h2{font-size:22px;font-weight:800;color:#111827;margin-bottom:12px}
.err-box p{font-size:15px;color:#6B7280;line-height:1.7;margin-bottom:28px}
.err-box a{display:inline-flex;align-items:center;gap:8px;background:#4F46E5;color:#fff;padding:13px 24px;border-radius:10px;font-weight:700;font-size:14px;transition:all .25s}
.err-box a:hover{background:#4338CA;transform:translateY(-2px);box-shadow:0 8px 24px rgba(79,70,229,.3)}
</style>
</head>
<body>
  <div class="err-box">
    <img src="images/icon.png" alt="Binest">
    <h1>404</h1>
    <h2>Page not found</h2>
    <p>The page you're looking for doesn't exist or has been moved. Let's get you back to business.</p>
    <a href="/"><i class="fas fa-arrow-left"></i> Back to Home</a>
  </div>
</body>
</html>
