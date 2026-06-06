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
    <title>Privacy Policy — BINEST</title>
    <meta name="description" content="BINEST Privacy Policy: Learn how we collect, use and protect your business data.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--blue:#1d4ed8;--blue-light:#eff6ff;--blue-mid:#dbeafe;--text:#111827;--muted:#6b7280;--border:#e5e7eb;--bg:#f9fafb;--white:#fff;--radius:12px;}
        *{margin:0;padding:0;box-sizing:border-box;}
        html{scroll-behavior:smooth;}
        body{font-family:'Inter',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--text);font-size:15px;line-height:1.75;}

        /* ── NAV ── */
        .nav{background:var(--white);border-bottom:1px solid var(--border);padding:0 24px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:200;}
        .nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);}
        .nav-logo{width:32px;height:32px;border-radius:8px;object-fit:contain;}
        .nav-name{font-size:16px;font-weight:600;letter-spacing:-.3px;}
        .nav-links{display:flex;align-items:center;gap:4px;}
        .nav-links a{padding:6px 14px;border-radius:6px;font-size:14px;font-weight:500;color:var(--muted);text-decoration:none;transition:all .15s;}
        .nav-links a:hover{color:var(--text);background:#f3f4f6;}
        .nav-links a.active{color:var(--blue);background:var(--blue-light);}
        .nav-cta{padding:7px 16px;background:var(--blue);color:#fff!important;border-radius:7px;font-size:13px;font-weight:600;text-decoration:none;transition:opacity .15s;}
        .nav-cta:hover{opacity:.88;background:var(--blue)!important;}

        /* ── HEADER ── */
        .page-header{background:var(--white);border-bottom:1px solid var(--border);padding:48px 24px 40px;}
        .page-header-inner{max-width:800px;margin:0 auto;}
        .page-header .badge{display:inline-flex;align-items:center;gap:6px;background:var(--blue-light);color:var(--blue);font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px;margin-bottom:16px;letter-spacing:.02em;text-transform:uppercase;}
        .page-header h1{font-size:34px;font-weight:700;letter-spacing:-.6px;color:var(--text);margin-bottom:12px;line-height:1.2;}
        .page-header .meta-row{display:flex;flex-wrap:wrap;gap:20px;color:var(--muted);font-size:13px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);}
        .page-header .meta-row span{display:flex;align-items:center;gap:6px;}

        /* ── LAYOUT ── */
        .layout{max-width:1140px;margin:0 auto;padding:40px 24px 80px;display:grid;grid-template-columns:240px 1fr;gap:40px;align-items:start;}

        /* ── SIDEBAR ── */
        .sidebar{position:sticky;top:76px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;}
        .sidebar-head{padding:14px 16px;border-bottom:1px solid var(--border);font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--muted);}
        .toc a{display:flex;align-items:center;gap:8px;padding:10px 16px;color:var(--muted);text-decoration:none;font-size:13px;font-weight:500;border-left:2px solid transparent;transition:all .15s;}
        .toc a:hover{color:var(--text);background:#f9fafb;}
        .toc a.active{color:var(--blue);background:var(--blue-light);border-left-color:var(--blue);font-weight:600;}
        .toc a .num{min-width:20px;font-size:11px;color:var(--muted);}
        .toc a.active .num{color:var(--blue);}

        /* ── CONTENT ── */
        .doc{background:var(--white);border:1px solid var(--border);border-radius:var(--radius);padding:48px;}

        .intro-box{background:var(--blue-light);border:1px solid var(--blue-mid);border-radius:10px;padding:18px 20px;font-size:14px;color:#1e40af;line-height:1.65;margin-bottom:40px;}

        .sec{padding-top:36px;margin-top:36px;border-top:1px solid var(--border);scroll-margin-top:76px;}
        .sec:first-of-type{border-top:none;margin-top:0;padding-top:0;}
        .sec-title{display:flex;align-items:center;gap:12px;margin-bottom:16px;}
        .sec-num{min-width:28px;height:28px;background:var(--blue-light);color:var(--blue);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;}
        .sec h2{font-size:19px;font-weight:600;color:var(--text);letter-spacing:-.2px;}
        .sec h3{font-size:14px;font-weight:600;color:var(--text);margin:20px 0 8px;text-transform:uppercase;letter-spacing:.04em;}
        .sec p{color:#374151;font-size:14.5px;margin-bottom:10px;}
        .sec ul{margin:10px 0 10px 0;list-style:none;}
        .sec ul li{position:relative;padding:5px 0 5px 20px;color:#374151;font-size:14.5px;}
        .sec ul li::before{content:'';position:absolute;left:0;top:13px;width:6px;height:6px;border-radius:50%;background:var(--blue);}

        .note{display:flex;gap:10px;align-items:flex-start;background:#fefce8;border:1px solid #fde68a;border-radius:8px;padding:12px 16px;font-size:13.5px;color:#92400e;margin:14px 0;}
        .note.green{background:#f0fdf4;border-color:#bbf7d0;color:#166534;}
        .note i{margin-top:2px;flex-shrink:0;}

        /* ── CONTACT ── */
        .contact-section{margin-top:40px;padding:28px;background:var(--bg);border:1px solid var(--border);border-radius:var(--radius);}
        .contact-section h3{font-size:16px;font-weight:600;margin-bottom:6px;}
        .contact-section p{font-size:13.5px;color:var(--muted);margin-bottom:20px;}
        .contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
        .contact-item{background:var(--white);border:1px solid var(--border);border-radius:9px;padding:16px;text-align:center;}
        .contact-item .ci-icon{width:36px;height:36px;background:var(--blue-light);color:var(--blue);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;margin:0 auto 10px;}
        .contact-item .ci-lbl{font-size:11px;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:.05em;}
        .contact-item .ci-val{font-size:13.5px;font-weight:600;color:var(--text);margin-top:3px;word-break:break-all;}
        .contact-item a{color:var(--blue);text-decoration:none;}

        /* ── FOOTER ── */
        .footer{background:var(--white);border-top:1px solid var(--border);padding:28px 24px;text-align:center;font-size:13px;color:var(--muted);}
        .footer-links{display:flex;justify-content:center;flex-wrap:wrap;gap:4px;margin-bottom:12px;}
        .footer-links a{padding:4px 10px;color:var(--muted);text-decoration:none;border-radius:5px;font-size:13px;}
        .footer-links a:hover{color:var(--text);}
        .footer-links span{color:var(--border);}

        /* ── MOBILE TOC TOGGLE ── */
        .toc-toggle{display:none;width:100%;padding:12px 16px;background:var(--white);border:1px solid var(--border);border-radius:var(--radius);font-size:14px;font-weight:500;color:var(--text);cursor:pointer;text-align:left;margin-bottom:16px;}
        .toc-toggle i{float:right;transition:transform .2s;}
        .toc-toggle.open i{transform:rotate(180deg);}

        /* ── RESPONSIVE ── */
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

<!-- NAV -->
<nav class="nav">
  <a href="index.php" class="nav-brand">
    <img src="images/icon.png" alt="Binest" class="nav-logo" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
    <div style="display:none;width:32px;height:32px;border-radius:8px;background:#1d4ed8;color:#fff;align-items:center;justify-content:center;font-weight:700;font-size:14px">B</div>
    <span class="nav-name">BINEST</span>
  </a>
  <div class="nav-links">
    <a href="index.php">Home</a>
    <a href="terms_of_service.php">Terms of Service</a>
    <a href="privacy_policy.php" class="active">Privacy Policy</a>
    <a href="mailto:<?= htmlspecialchars($support_email) ?>" class="nav-cta">Contact Us</a>
  </div>
</nav>

<!-- PAGE HEADER -->
<div class="page-header">
  <div class="page-header-inner">
    <div class="badge"><i class="fas fa-shield-halved"></i> Legal</div>
    <h1>Privacy Policy</h1>
    <p style="color:#374151;font-size:15px;max-width:640px">This policy describes how BINEST collects, uses, and protects your personal and business information when you use our app and services.</p>
    <div class="meta-row">
      <span><i class="fas fa-calendar"></i> Last updated: February 13, 2026</span>
      <span><i class="fas fa-building"></i> BINEST Business Manager</span>
      <span><i class="fas fa-envelope"></i> <?= htmlspecialchars($support_email) ?></span>
    </div>
  </div>
</div>

<!-- LAYOUT -->
<div class="layout">

  <!-- SIDEBAR -->
  <aside>
    <button class="toc-toggle" onclick="var s=document.getElementById('mob-toc');s.classList.toggle('mob-open');this.classList.toggle('open')">
      Table of Contents <i class="fas fa-chevron-down"></i>
    </button>
    <div class="sidebar" id="mob-toc">
      <div class="sidebar-head">Contents</div>
      <nav class="toc">
        <a href="#s1"><span class="num">1</span> Information We Collect</a>
        <a href="#s2"><span class="num">2</span> How We Use Information</a>
        <a href="#s3"><span class="num">3</span> Data Storage &amp; Security</a>
        <a href="#s4"><span class="num">4</span> Data Sharing</a>
        <a href="#s5"><span class="num">5</span> Data Retention</a>
        <a href="#s6"><span class="num">6</span> Your Rights</a>
        <a href="#s7"><span class="num">7</span> Notifications</a>
        <a href="#s8"><span class="num">8</span> Children's Privacy</a>
        <a href="#s9"><span class="num">9</span> Third-Party Services</a>
        <a href="#s10"><span class="num">10</span> Policy Changes</a>
        <a href="#s11"><span class="num">11</span> Contact Us</a>
      </nav>
    </div>
  </aside>

  <!-- DOCUMENT -->
  <main class="doc">

    <div class="intro-box">
      <strong>BINEST</strong> ("Company", "we", "us", or "our") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our BINEST mobile application. Please read this policy carefully. By using the App, you agree to the terms described herein.
    </div>

    <section class="sec" id="s1">
      <div class="sec-title"><div class="sec-num">1</div><h2>Information We Collect</h2></div>
      <h3>1.1 Personal Information</h3>
      <p>When you create an account, we collect:</p>
      <ul>
        <li>Full name</li>
        <li>Business name</li>
        <li>Mobile number</li>
        <li>Email address</li>
        <li>Password (encrypted using industry-standard hashing)</li>
      </ul>
      <h3>1.2 Business Data</h3>
      <p>While using the App, you may provide:</p>
      <ul>
        <li>Customer information (name, contact details, address)</li>
        <li>Product and inventory details</li>
        <li>Bills, invoices, and transaction records</li>
        <li>Expense records</li>
        <li>Due/Udhari payment records</li>
        <li>Business reports and analytics data</li>
      </ul>
      <h3>1.3 Automatically Collected</h3>
      <ul>
        <li>Device type and model</li>
        <li>Operating system version</li>
        <li>App version &amp; usage patterns</li>
        <li>Error logs for debugging purposes</li>
        <li>GPS location (if enabled, for delivery tracking)</li>
      </ul>
    </section>

    <section class="sec" id="s2">
      <div class="sec-title"><div class="sec-num">2</div><h2>How We Use Your Information</h2></div>
      <ul>
        <li>To create and manage your account</li>
        <li>To provide billing, inventory, and business management services</li>
        <li>To generate business reports and analytics</li>
        <li>To send notifications (due dates, daily reports, store timings)</li>
        <li>To enable data backup and restore functionality</li>
        <li>To improve and optimize the App</li>
        <li>To provide customer support</li>
        <li>To verify your identity via OTP verification</li>
      </ul>
    </section>

    <section class="sec" id="s3">
      <div class="sec-title"><div class="sec-num">3</div><h2>Data Storage and Security</h2></div>
      <p>Your data is stored on our secure servers hosted in India with industry-standard security measures:</p>
      <ul>
        <li>Password encryption using secure hashing algorithms</li>
        <li>HTTPS encryption for all data transmission</li>
        <li>Regular security audits and updates</li>
        <li>Access controls to limit data access to authorized personnel</li>
      </ul>
      <div class="note"><i class="fas fa-triangle-exclamation"></i><div><strong>Note:</strong> While we implement strong security measures, no method of electronic storage is 100% secure. We cannot guarantee absolute security of your data.</div></div>
    </section>

    <section class="sec" id="s4">
      <div class="sec-title"><div class="sec-num">4</div><h2>Data Sharing and Disclosure</h2></div>
      <div class="note green"><i class="fas fa-circle-check"></i><div><strong>We do NOT sell your personal or business data to any third party or share with advertisers.</strong></div></div>
      <p>We may disclose your information only in the following limited circumstances:</p>
      <ul>
        <li>When required by law or legal process</li>
        <li>To protect our rights, privacy, safety, or property</li>
        <li>To enforce our Terms &amp; Conditions</li>
        <li>With your explicit prior consent</li>
      </ul>
      <p>In case of a business transfer, merger, or acquisition, your data may be transferred to the new entity with prior notification to you.</p>
    </section>

    <section class="sec" id="s5">
      <div class="sec-title"><div class="sec-num">5</div><h2>Data Retention</h2></div>
      <ul>
        <li>We retain your data as long as your account is active</li>
        <li>Upon account deletion, personal data is removed within 30 days</li>
        <li>Anonymized data may be retained for service improvement purposes</li>
        <li>Certain data may be retained as required by applicable Indian laws</li>
      </ul>
    </section>

    <section class="sec" id="s6">
      <div class="sec-title"><div class="sec-num">6</div><h2>Your Rights</h2></div>
      <p>You have the following rights regarding your personal and business data:</p>
      <ul>
        <li><strong>Access —</strong> View all your data within the App</li>
        <li><strong>Export —</strong> Download your data via the backup feature</li>
        <li><strong>Correction —</strong> Update your information in Profile settings</li>
        <li><strong>Deletion —</strong> Request account and data deletion at any time</li>
        <li><strong>Portability —</strong> Download data in CSV and PDF formats</li>
        <li><strong>Objection —</strong> Opt-out of non-essential notifications</li>
      </ul>
    </section>

    <section class="sec" id="s7">
      <div class="sec-title"><div class="sec-num">7</div><h2>Notifications</h2></div>
      <p>The App may send local push notifications for:</p>
      <ul>
        <li>Due date payment reminders</li>
        <li>Daily business report summaries</li>
        <li>Store open/close time reminders</li>
        <li>Low stock alerts</li>
      </ul>
      <p>You can manage and disable notification preferences within the App settings at any time.</p>
    </section>

    <section class="sec" id="s8">
      <div class="sec-title"><div class="sec-num">8</div><h2>Children's Privacy</h2></div>
      <p>The App is not intended for individuals under 18 years of age. We do not knowingly collect personal information from minors. If we discover that we have inadvertently collected data from a child under 18, we will delete such information promptly. If you are a parent or guardian and believe your child has provided us with personal data, please contact us immediately.</p>
    </section>

    <section class="sec" id="s9">
      <div class="sec-title"><div class="sec-num">9</div><h2>Third-Party Services</h2></div>
      <p>The App integrates the following third-party services:</p>
      <ul>
        <li><strong>Expo —</strong> App framework and push notification delivery</li>
        <li><strong>AsyncStorage —</strong> Local device data caching</li>
        <li><strong>OpenStreetMap —</strong> Map and location services (where applicable)</li>
      </ul>
      <p>Each of these services operates under their own privacy policies. We encourage you to review them. We are not responsible for the data practices of third-party services.</p>
    </section>

    <section class="sec" id="s10">
      <div class="sec-title"><div class="sec-num">10</div><h2>Changes to This Policy</h2></div>
      <ul>
        <li>We may update this Privacy Policy from time to time at our discretion</li>
        <li>Significant changes will be communicated via in-app notifications or email</li>
        <li>Continued use of the App after updates constitutes acceptance of the revised policy</li>
        <li>We recommend reviewing this policy periodically to stay informed</li>
      </ul>
    </section>

    <section class="sec" id="s11">
      <div class="sec-title"><div class="sec-num">11</div><h2>Contact Us</h2></div>
      <p>If you have any questions, concerns, or requests regarding this Privacy Policy or your data, please reach out to us:</p>
      <div class="contact-section">
        <h3>Get In Touch</h3>
        <p>Our team typically responds within 1–2 business days.</p>
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

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-links">
    <a href="index.php">Home</a><span>·</span>
    <a href="privacy_policy.php">Privacy Policy</a><span>·</span>
    <a href="terms_of_service.php">Terms of Service</a><span>·</span>
    <a href="mailto:<?= htmlspecialchars($support_email) ?>">Contact</a>
  </div>
  <div>&copy; <?= date('Y') ?> BINEST. All rights reserved. · By using BINEST, you agree to this Privacy Policy.</div>
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
