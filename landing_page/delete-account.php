<?php
$_dataDir = __DIR__ . '/data/';
$_cfg = [];
$_sf2 = $_dataDir . 'settings.json';
if (file_exists($_sf2)) $_cfg = json_decode(file_get_contents($_sf2), true) ?? [];
$_mail = $_cfg['email'] ?? 'binestmanage@gmail.com';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Delete Account — Binest</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:Inter,sans-serif;background:#F8FAFC;color:#1E293B;line-height:1.8}
    header{background:linear-gradient(135deg,#DC2626,#9F1239);color:#fff;padding:48px 24px;text-align:center}
    header img{width:48px;height:48px;border-radius:12px;margin-bottom:12px}
    header h1{font-size:26px;font-weight:800}
    header p{font-size:14px;opacity:.8;margin-top:6px}
    .container{max-width:680px;margin:40px auto;padding:0 20px 60px}
    .card{background:#fff;border:1px solid #E2E8F0;border-radius:16px;padding:36px;margin-bottom:24px}
    .card.warn{border-color:#FCA5A5;background:#FFF5F5}
    h2{font-size:18px;font-weight:800;color:#0F172A;margin-bottom:12px;padding-bottom:10px;border-bottom:2px solid #F1F5F9}
    p,li{font-size:14px;color:#475569;margin-bottom:10px}
    ul{padding-left:20px}
    .back{display:inline-flex;align-items:center;gap:8px;color:#4F46E5;text-decoration:none;font-size:14px;font-weight:600;margin-bottom:24px}
    .back:hover{color:#7C3AED}
    .steps{counter-reset:step;list-style:none;padding:0}
    .steps li{display:flex;gap:14px;margin-bottom:16px;align-items:flex-start}
    .steps li::before{counter-increment:step;content:counter(step);min-width:28px;height:28px;border-radius:50%;background:#DC2626;color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:800;flex-shrink:0;margin-top:2px}
    .btn-email{display:inline-flex;align-items:center;gap:8px;background:#DC2626;color:#fff;padding:12px 24px;border-radius:10px;font-size:15px;font-weight:700;text-decoration:none;margin-top:14px}
    .btn-email:hover{background:#B91C1C}
  </style>
</head>
<body>
<header>
  <img src="images/icon.png" alt="Binest" onerror="this.style.display='none'">
  <h1>Delete Account</h1>
  <p>Request permanent deletion of your Binest account and data</p>
</header>
<div class="container">
  <a href="index.php" class="back">&#8592; Back to Home</a>

  <div class="card warn">
    <h2>⚠️ Before You Delete</h2>
    <p>Deleting your account is <strong>permanent and irreversible</strong>. Please be aware:</p>
    <ul>
      <li>All your business data (bills, products, customers, expenses) will be permanently deleted</li>
      <li>Your subscription will be cancelled immediately with no refund</li>
      <li>You will lose access to all reports and history</li>
      <li>This action cannot be undone</li>
    </ul>
  </div>

  <div class="card">
    <h2>How to Delete Your Account</h2>
    <ol class="steps">
      <li><span>Send an email to us with your registered phone number or email address</span></li>
      <li><span>Use the subject line: <strong>"Account Deletion Request — [Your Business Name]"</strong></span></li>
      <li><span>We will verify your identity and process the deletion within <strong>7 working days</strong></span></li>
      <li><span>You will receive a confirmation email once your account is fully deleted</span></li>
    </ol>
    <a href="mailto:<?= htmlspecialchars($_mail) ?>?subject=Account%20Deletion%20Request&body=Business%20Name%3A%0ARegistered%20Phone%2FEmail%3A%0AReason%20(optional)%3A"
       class="btn-email">
      ✉️ Send Deletion Request
    </a>
  </div>

  <div class="card">
    <h2>Data Retention Policy</h2>
    <p>After your deletion request is processed:</p>
    <ul>
      <li>Your personal data is deleted within <strong>7 working days</strong></li>
      <li>Anonymized, aggregated data may be retained for analytics</li>
      <li>Backup copies are purged within <strong>30 days</strong></li>
    </ul>
    <p style="margin-top:14px">Questions? Contact: <a href="mailto:<?= htmlspecialchars($_mail) ?>" style="color:#DC2626"><?= htmlspecialchars($_mail) ?></a></p>
  </div>
</div>
</body>
</html>
