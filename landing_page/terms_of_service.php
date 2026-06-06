<?php
require_once __DIR__ . '/db.php';
$cfg           = lpGetSettings();
$support_email = $cfg['email']    ?? 'binestmanage@gmail.com';
$support_phone = $cfg['whatsapp'] ?? '7608081767';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service — BINEST Business Manager</title>
    <meta name="description" content="BINEST Terms & Conditions: The legal agreement between you and BINEST for using our business management app.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--purple:#6d28d9;--purple-light:#f5f3ff;--purple-mid:#ede9fe;--text:#111827;--muted:#6b7280;--border:#e5e7eb;--bg:#f9fafb;--white:#fff;--radius:12px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--text);font-size:15px;line-height:1.75;}
        .nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);}
        .nav-logo{width:32px;height:32px;border-radius:8px;object-fit:contain;}
        .nav-name{font-size:16px;font-weight:600;letter-spacing:-.3px;}
        .nav-links{display:flex;align-items:center;gap:4px;}
        .nav-links a{padding:6px 14px;border-radius:6px;font-size:14px;font-weight:500;color:var(--muted);text-decoration:none;transition:all .15s;}
        .nav-links a:hover{color:var(--text);background:#f3f4f6;}
        .nav-links a.active{color:var(--purple);background:var(--purple-light);}
        .nav-cta{padding:7px 16px;background:var(--purple);color:#fff!important;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;transition:opacity .15s;}
        .nav-cta:hover{opacity:.88;background:var(--purple)!important;}
        .page-header{background:var(--white);border-bottom:1px solid var(--border);padding:48px 24px 40px;}
        .page-header-inner{max-width:800px;margin:0 auto;}
        .page-header .badge{display:inline-flex;align-items:center;gap:6px;background:var(--purple-light);color:var(--purple);font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;margin-bottom:16px;letter-spacing:.02em;text-transform:uppercase;}
        .page-header h1{font-size:34px;font-weight:700;letter-spacing:-.6px;color:var(--text);margin-bottom:12px;line-height:1.2;}
        .page-header .meta-row{display:flex;flex-wrap:wrap;gap:20px;color:var(--muted);font-size:13px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);}
        .page-header .meta-row span{display:flex;align-items:center;gap:6px;}
        .layout{max-width:1140px;margin:0 auto;padding:40px 24px 80px;display:grid;grid-template-columns:240px 1fr;gap:40px;align-items:start;}
        .sidebar{position:sticky;top:76px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
        .sidebar-head{padding:14px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);}
        .toc a{display:flex;align-items:center;gap:8px;padding:10px 16px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;border-left:2px solid transparent;transition:all .15s;}
        .toc a:hover{color:var(--text);background:#f9fafb;}
        .toc a.active{color:var(--purple);background:var(--purple-light);border-left-color:var(--purple);font-weight:600;}
        .toc a .num{min-width:20px;font-size:11px;color:var(--muted);}
        .toc a.active .num{color:var(--purple);}
        .doc{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:48px;}
        .intro-box{background:var(--purple-light);border:1px solid var(--purple-mid);border-radius:10px;padding:18px 20px;font-size:14px;color:#4c1d95;line-height:1.65;margin-bottom:40px;}
        .sec{padding-top:36px;margin-top:36px;border-top:1px solid var(--border);scroll-margin-top:76px;}
        .sec:first-of-type{border-top:none;margin-top:0;padding-top:0;}
        .sec-title{display:flex;align-items:center;gap:12px;margin-bottom:16px;}
        .sec-num{min-width:28px;height:28px;background:var(--purple-light);color:var(--purple);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;}
        .sec h2{font-size:19px;font-weight:600;color:var(--text);letter-spacing:-.2px;}
        .sec p{color:#374151;font-size:14.5px;margin-bottom:10px;}
        .sec a{color:var(--purple);font-weight:500;text-decoration:none;}
        .sec a:hover{text-decoration:underline;}
        .sec ul{margin:10px 0;list-style:none;}
        .sec ul li{position:relative;padding:5px 0 5px 20px;color:#374151;font-size:14.5px;}
        .sec ul li::before{content:'';position:absolute;left:0;top:13px;width:6px;height:6px;border-radius:50%;background:var(--purple);}
        .note{display:flex;gap:10px;align-items:flex-start;background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:13.5px;color:#92400e;margin:14px 0;}
        .note.green{background:#f0fdf4;border-color:#bbf7d0;color:#166534;}
        .note i{margin-top:2px;flex-shrink:0;}
        .contact-section{margin-top:40px;padding:28px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);}
        .contact-section h3{font-size:16px;font-weight:600;margin-bottom:6px;}
        .contact-section p{font-size:13.5px;color:var(--muted);margin-bottom:20px;}
        .contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
        .contact-item{background:var(--white);border:1px solid var(--border);border-radius:9px;padding:16px;text-align:center;}
        .contact-item .ci-icon{width:36px;height:36px;background:var(--purple-light);color:var(--purple);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;margin:0 auto 10px;}
        .contact-item .ci-lbl{font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
        .contact-item .ci-val{font-size:13.5px;font-weight:600;color:var(--text);margin-top:3px;word-break:break-all;}
        .contact-item a{color:var(--purple);text-decoration:none;}
        .footer{background:var(--white);border-top:1px solid var(--border);padding:28px 24px;text-align:center;font-size:13px;color:var(--muted);}
        .footer-links{display:flex;justify-content:center;flex-wrap:wrap;gap:4px;margin-bottom:12px;}
        .footer-links a{padding:4px 10px;color:var(--muted);text-decoration:none;border-radius:5px;font-size:13px;}
        .footer-links a:hover{color:var(--text);}
        .footer-links span{color:var(--border);}
        .toc-toggle{display:none;width:100%;padding:12px 16px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);font-size:14px;font-weight:500;color:var(--text);cursor:pointer;text-align:left;margin-bottom:16px;}
        .toc-toggle i{float:right;transition:transform .2s;}
        .toc-toggle.open i{transform:rotate(180deg);}
        @media(max-width:900px){
            .layout{grid-template-columns:1fr;gap:20px;padding:24px 16px 60px;}
            .sidebar{position:static;display:none;}
            .sidebar.mob-open{display:block;}
            .toc-toggle{display:block;}
            .doc{padding:24px 20px;}
            .page-header h1{font-size:26px;}
            .contact-grid{grid-template-columns:1fr;}
        }
        @media(max-width:600px){
            .nav-links{display:none;}
            .page-header{padding:28px 16px 24px;}
            .page-header h1{font-size:22px;}
            .doc{padding:20px 16px;}
            .sec h2{font-size:17px;}
        }
    </style>
</head>
<body>

<nav class="nav">
  <a href="index.php" class="nav-brand">
    <img src="images/icon.png" alt="Binest" class="nav-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div style="display:none;width:32px;height:32px;border-radius:8px;background:#6d28d9;color:#fff;align-items:center;justify-content:center;font-weight:700;font-size:14px">B</div>
    <span class="nav-name">BINEST</span>
  </a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="terms_of_service.php" class="active">Terms of Service</a>
    <a href="privacy_policy.php">Privacy Policy</a>
    <a href="mailto:<?= htmlspecialchars($support_email) ?>" class="nav-cta">Contact Us</a>
  </div>
</nav>

<div class="page-header">
  <div class="page-header-inner">
    <div class="badge"><i class="fas fa-file-contract"></i> Legal</div>
    <h1>Terms of Service</h1>
    <p style="color:#374151;font-size:15px;max-width:640px">This is the legal agreement between you and BINEST. By using our app and services, you agree to be bound by these terms.</p>
    <div class="meta-row">
      <span><i class="fas fa-calendar"></i> Last updated: February 13, 2026</span>
      <span><i class="fas fa-building"></i> BINEST Business Manager</span>
      <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($support_email) ?></span>
    </div>
  </div>
</div>

<div class="layout">
  <aside>
    <button class="toc-toggle" onclick="var s=document.getElementById('mob-toc');s.classList.toggle('mob-open');this.classList.toggle('open')">
      Table of Contents <i class="fas fa-chevron-down"></i>
    </button>
    <div class="sidebar" id="mob-toc">
      <div class="sidebar-head">Contents</div>
      <nav class="toc">
        <a href="#s1"><span class="num">1</span> Acceptance of Terms</a>
        <a href="#s2"><span class="num">2</span> Description of Service</a>
        <a href="#s3"><span class="num">3</span> User Account</a>
        <a href="#s4"><span class="num">4</span> User Responsibilities</a>
        <a href="#s5"><span class="num">5</span> Data and Privacy</a>
        <a href="#s6"><span class="num">6</span> Intellectual Property</a>
        <a href="#s7"><span class="num">7</span> Service Availability</a>
        <a href="#s8"><span class="num">8</span> Limitation of Liability</a>
        <a href="#s9"><span class="num">9</span> Account Termination</a>
        <a href="#s10"><span class="num">10</span> Changes to Terms</a>
        <a href="#s11"><span class="num">11</span> Governing Law</a>
        <a href="#s12"><span class="num">12</span> Contact Us</a>
      </nav>
    </div>
  </aside>

  <main class="doc">
    <div class="intro-box">
      Welcome to <strong>BINEST</strong>. These Terms of Service ("Terms") constitute a legally binding agreement between you ("User") and <strong>BINEST</strong> ("Company", "we", "us", "our"). By downloading, installing, or using the App, you confirm that you have read, understood, and agreed to these Terms. If you do not agree, please do not use the App.
    </div>

    <section class="sec" id="s1">
      <div class="sec-title"><div class="sec-num">1</div><h2>Acceptance of Terms</h2></div>
      <p>By downloading, installing, or using the BINEST application ("App"), you agree to be bound by these Terms. If you do not agree to any part of these Terms, you must not use the App.</p>
      <div class="note green"><i class="fas fa-circle-check"></i><div>Your continued use of the App constitutes your acceptance of these Terms and our <a href="privacy_policy.php">Privacy Policy</a>.</div></div>
    </section>

    <section class="sec" id="s2">
      <div class="sec-title"><div class="sec-num">2</div><h2>Description of Service</h2></div>
      <p>BINEST is a comprehensive business management application designed for small and medium-sized businesses. Our services include:</p>
      <ul>
        <li>Billing and invoicing system</li>
        <li>Inventory and stock management</li>
        <li>Customer relationship management (CRM)</li>
        <li>Expense tracking and financial reporting</li>
        <li>Due/Udhari payment tracking</li>
        <li>Business analytics and reports</li>
        <li>Notification and reminder system</li>
        <li>Data backup and restore functionality</li>
      </ul>
    </section>

    <section class="sec" id="s3">
      <div class="sec-title"><div class="sec-num">3</div><h2>User Account</h2></div>
      <ul>
        <li>You must register with accurate information (name, business name, mobile number, email)</li>
        <li>You are solely responsible for maintaining the confidentiality of your credentials</li>
        <li>You must be at least <strong>18 years of age</strong> to use the App</li>
        <li>Providing false or misleading information may result in immediate account suspension</li>
        <li>One account per user/business — creation of multiple accounts is not permitted</li>
      </ul>
    </section>

    <section class="sec" id="s4">
      <div class="sec-title"><div class="sec-num">4</div><h2>User Responsibilities</h2></div>
      <p>You agree to use the App solely for lawful business purposes. You shall <strong>NOT</strong>:</p>
      <ul>
        <li>Engage in fraudulent, deceptive, or illegal activities</li>
        <li>Transmit harmful, threatening, abusive, or defamatory content</li>
        <li>Attempt to gain unauthorized access to other users' data or accounts</li>
        <li>Reverse engineer, decompile, disassemble, or tamper with the App</li>
        <li>Use the App in any manner that violates applicable laws or regulations</li>
      </ul>
      <div class="note"><i class="fas fa-triangle-exclamation"></i><div>You are solely responsible for the accuracy of all business data entered into the App. We strongly recommend maintaining regular data backups — we are not liable for any data loss.</div></div>
    </section>

    <section class="sec" id="s5">
      <div class="sec-title"><div class="sec-num">5</div><h2>Data and Privacy</h2></div>
      <ul>
        <li>Your use of the App is also governed by our <a href="privacy_policy.php">Privacy Policy</a></li>
        <li>We store your data on secure, encrypted servers to deliver our services</li>
        <li>We do NOT sell, rent, or share your data with any third parties</li>
        <li>You retain full ownership of all business data you enter into the App</li>
      </ul>
    </section>

    <section class="sec" id="s6">
      <div class="sec-title"><div class="sec-num">6</div><h2>Intellectual Property</h2></div>
      <ul>
        <li>The App, its design, source code, logos, and all content are the exclusive intellectual property of BINEST</li>
        <li>You are granted a limited, non-exclusive, non-transferable, revocable license for personal business use only</li>
        <li>You may not copy, modify, distribute, sell, sublicense, or lease any part of the App without written consent</li>
      </ul>
    </section>

    <section class="sec" id="s7">
      <div class="sec-title"><div class="sec-num">7</div><h2>Service Availability</h2></div>
      <ul>
        <li>We strive to maintain 24/7 service availability but cannot guarantee uninterrupted access</li>
        <li>We may temporarily suspend the App for scheduled maintenance, updates, or emergency repairs</li>
        <li>We reserve the right to modify, suspend, or discontinue any feature or the entire service at any time</li>
      </ul>
    </section>

    <section class="sec" id="s8">
      <div class="sec-title"><div class="sec-num">8</div><h2>Limitation of Liability</h2></div>
      <div class="note"><i class="fas fa-scale-balanced"></i><div>The App is provided <strong>"as is"</strong> and <strong>"as available"</strong> without warranties of any kind, express or implied.</div></div>
      <ul>
        <li>We are not liable for any direct, indirect, incidental, special, or consequential damages</li>
        <li>We are not responsible for financial losses, accounting errors, or business interruptions</li>
        <li>Our total liability shall not exceed the amount paid by you in the 12 months preceding any claim</li>
      </ul>
    </section>

    <section class="sec" id="s9">
      <div class="sec-title"><div class="sec-num">9</div><h2>Account Termination</h2></div>
      <ul>
        <li>You may delete your account at any time through the App settings</li>
        <li>We reserve the right to suspend or terminate accounts found to be in violation of these Terms</li>
        <li>Upon termination, your data may be deleted after a reasonable retention period as required by law</li>
      </ul>
    </section>

    <section class="sec" id="s10">
      <div class="sec-title"><div class="sec-num">10</div><h2>Changes to Terms</h2></div>
      <ul>
        <li>We reserve the right to modify these Terms at any time at our sole discretion</li>
        <li>Continued use of the App after modifications constitutes your acceptance of the revised Terms</li>
        <li>Significant changes will be communicated via in-app notifications or email</li>
      </ul>
    </section>

    <section class="sec" id="s11">
      <div class="sec-title"><div class="sec-num">11</div><h2>Governing Law</h2></div>
      <p>These Terms shall be governed by and construed in accordance with the <strong>laws of India</strong>. Any disputes arising out of or relating to these Terms or the use of the App shall be subject to the exclusive jurisdiction of the competent courts in India.</p>
    </section>

    <section class="sec" id="s12">
      <div class="sec-title"><div class="sec-num">12</div><h2>Contact Us</h2></div>
      <p>If you have any questions, concerns, or feedback regarding these Terms of Service, please contact us:</p>
      <div class="contact-section">
        <h3>Get In Touch</h3>
        <p>Our support team is available to assist with any legal or account-related questions.</p>
        <div class="contact-grid">
          <div class="contact-item">
            <div class="ci-icon"><i class="fas fa-envelope"></i></div>
            <div class="ci-lbl">Email</div>
            <div class="ci-val"><a href="mailto:<?= htmlspecialchars($support_email) ?>"><?= htmlspecialchars($support_email) ?></a></div>
          </div>
          <div class="contact-item">
            <div class="ci-icon"><i class="fab fa-whatsapp"></i></div>
            <div class="ci-lbl">WhatsApp</div>
            <div class="ci-val"><a href="https://wa.me/<?= preg_replace('/\D/','',$support_phone) ?>"><?= htmlspecialchars($support_phone) ?></a></div>
          </div>
          <div class="contact-item">
            <div class="ci-icon"><i class="fas fa-mobile-screen"></i></div>
            <div class="ci-lbl">App</div>
            <div class="ci-val">BINEST Manager</div>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>

<footer class="footer">
  <div class="footer-links">
    <a href="index.php">Home</a><span>·</span>
    <a href="privacy_policy.php">Privacy Policy</a><span>·</span>
    <a href="terms_of_service.php">Terms of Service</a><span>·</span>
    <a href="mailto:<?= htmlspecialchars($support_email) ?>">Contact</a>
  </div>
  <div>&copy; <?= date('Y') ?> BINEST. All rights reserved. · By using BINEST, you agree to these Terms of Service.</div>
</footer>

<script>
const sections = document.querySelectorAll('.sec[id]');
const links = document.querySelectorAll('.toc a');
window.addEventListener('scroll', () => {
  let cur = '';
  sections.forEach(s => { if (window.scrollY >= s.offsetTop - 90) cur = s.id; });
  links.forEach(l => l.classList.toggle('active', l.getAttribute('href') === '#' + cur));
});
links.forEach(l => l.addEventListener('click', e => {
  e.preventDefault();
  const t = document.querySelector(l.getAttribute('href'));
  if (t) window.scrollTo({ top: t.offsetTop - 70, behavior: 'smooth' });
  if (window.innerWidth <= 900) {
    document.getElementById('mob-toc').classList.remove('mob-open');
    document.querySelector('.toc-toggle').classList.remove('open');
  }
}));
</script>
</body>
</html>
