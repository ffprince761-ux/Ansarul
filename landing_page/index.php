<?php
require_once __DIR__ . '/db.php';
lpIncStat('page_views');
$cfg  = lpGetSettings();
$wa   = !empty($cfg['whatsapp']) ? 'https://wa.me/' . preg_replace('/[^0-9]/', '', $cfg['whatsapp']) : '';
$mail = $cfg['email']       ?? 'binestmanage@gmail.com';
$apkV = $cfg['apk_version'] ?? '1.0.0';
$ps      = $cfg['play_store']    ?? '';
$vid     = $cfg['video_url']     ?? '';
$psMode  = $cfg['playstore_mode'] ?? 'link';
$showApk = ($cfg['show_apk']      ?? '1') === '1';
$showWin = ($cfg['show_windows']  ?? '0') === '1';
$winUrl  = $cfg['windows_url']    ?? '';
$ig   = (!empty($cfg['instagram']) && $cfg['instagram'] !== '#') ? $cfg['instagram'] : '';
$fb   = (!empty($cfg['facebook'])  && $cfg['facebook']  !== '#') ? $cfg['facebook']  : '';
$yt   = (!empty($cfg['youtube'])   && $cfg['youtube']   !== '#') ? $cfg['youtube']   : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="Binest — Smart billing, inventory & business management app for Indian small businesses. Free download.">
<meta name="keywords" content="billing app, inventory management, small business, shop management, Indian business app, free billing software, kirana store app, due tracking, udhari management">
<meta name="author" content="Binest">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#4F46E5">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($cfg['lp_page_url'] ?? 'https://binest.app') ?>">
<meta property="og:title" content="Binest — Smart Business Management App">
<meta property="og:description" content="Billing, inventory, customers, expenses & reports — one simple app built for Indian small businesses. Free to get started.">
<meta property="og:image" content="<?= htmlspecialchars($cfg['lp_page_url'] ?? '') ?>/images/banner.png">
<meta property="og:locale" content="en_IN">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="<?= htmlspecialchars($cfg['lp_page_url'] ?? 'https://binest.app') ?>">
<meta property="twitter:title" content="Binest — Smart Business Management App">
<meta property="twitter:description" content="Billing, inventory, customers, expenses & reports — one simple app built for Indian small businesses. Free to get started.">
<meta property="twitter:image" content="<?= htmlspecialchars($cfg['lp_page_url'] ?? '') ?>/images/banner.png">

<!-- Canonical URL -->
<link rel="canonical" href="<?= htmlspecialchars($cfg['lp_page_url'] ?? 'https://binest.app') ?>">

<!-- Preconnect for performance -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdnjs.cloudflare.com">

<title>Binest — Smart Business Management App</title>
<link rel="icon" href="images/icon.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ─── RESET & BASE ─── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth;font-size:16px}
body{font-family:'Inter',sans-serif;color:#111827;background:#fff;overflow-x:hidden;-webkit-font-smoothing:antialiased}
a{text-decoration:none;color:inherit}
img{max-width:100%;display:block}
*:focus-visible{outline:2px solid var(--indigo);outline-offset:2px;border-radius:4px}
::-webkit-scrollbar{width:5px}
::-webkit-scrollbar-thumb{background:#E5E7EB;border-radius:3px}

/* ─── VARIABLES ─── */
:root{
  --indigo:#4F46E5;--indigo-d:#4338CA;--indigo-l:#EEF2FF;
  --amber:#F59E0B;--green:#059669;--red:#EF4444;
  --text:#111827;--muted:#6B7280;--light:#9CA3AF;
  --border:#E5E7EB;--bg:#F9FAFB;--white:#fff;
}

/* ─── NAV ─── */
.nav{position:fixed;top:0;left:0;right:0;z-index:9000;height:70px;background:rgba(255,255,255,.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 5%;transition:box-shadow .3s}
.nav.scrolled{box-shadow:0 4px 24px rgba(0,0,0,.06)}
.logo{display:flex;align-items:center;gap:10px}
.logo img{width:36px;height:36px;border-radius:10px}
.logo-name{font-size:20px;font-weight:900;color:var(--text);letter-spacing:-.5px}
.logo-name span{color:var(--indigo)}
.nav-links{display:flex;align-items:center;gap:32px}
.nav-links a{font-size:14.5px;font-weight:500;color:var(--muted);transition:color .2s}
.nav-links a:hover{color:var(--text)}
.nav-actions{display:flex;align-items:center;gap:10px}
.lang-switch{position:relative}
.lang-switch select{appearance:none;background:transparent;border:1.5px solid var(--border);border-radius:8px;padding:7px 28px 7px 12px;font-size:12px;font-weight:600;color:var(--muted);font-family:'Inter',sans-serif;cursor:pointer;outline:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236B7280' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center}
.lang-switch select:focus{border-color:var(--indigo)}
.btn-ghost{font-size:14px;font-weight:600;color:var(--muted);padding:9px 18px;border-radius:8px;border:1.5px solid var(--border);background:transparent;transition:all .2s;cursor:pointer}
.btn-ghost:hover{border-color:var(--indigo);color:var(--indigo)}
.btn-primary{display:inline-flex;align-items:center;gap:8px;font-size:14px;font-weight:700;color:#fff;background:var(--indigo);padding:10px 22px;border-radius:8px;border:none;transition:all .25s;cursor:pointer;box-shadow:0 4px 14px rgba(79,70,229,.3)}
.btn-primary:hover{background:var(--indigo-d);transform:translateY(-1px);box-shadow:0 8px 20px rgba(79,70,229,.4)}

/* ─── HERO ─── */
.hero{min-height:100vh;display:flex;align-items:center;padding:100px 5% 60px;background:var(--white);position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:0;right:0;width:55%;height:100%;background:linear-gradient(135deg,#EEF2FF 0%,#E0E7FF 40%,#EDE9FE 100%);z-index:0;clip-path:polygon(8% 0,100% 0,100% 100%,0 100%)}
.hero-inner{max-width:1200px;margin:0 auto;width:100%;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;position:relative;z-index:1}
.hero-left{display:flex;flex-direction:column;align-items:flex-start}
.hero-tag{display:inline-flex;align-items:center;gap:7px;background:var(--indigo-l);color:var(--indigo);font-size:12px;font-weight:700;padding:6px 14px;border-radius:999px;margin-bottom:22px;letter-spacing:.04em}
.tag-dot{width:6px;height:6px;background:var(--indigo);border-radius:50%;animation:td 2s ease infinite}
@keyframes td{0%,100%{opacity:1}50%{opacity:.4}}
.hero-h1{font-size:clamp(36px,4.5vw,58px);font-weight:900;line-height:1.1;letter-spacing:-1.5px;color:var(--text);margin-bottom:20px}
.hero-h1 em{font-style:normal;color:var(--indigo)}
.hero-desc{font-size:17px;color:var(--muted);line-height:1.8;margin-bottom:36px;max-width:480px}
.hero-cta{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:44px}
.cta-main{display:inline-flex;align-items:center;gap:9px;background:var(--indigo);color:#fff;padding:14px 28px;border-radius:10px;font-weight:800;font-size:16px;transition:all .28s;box-shadow:0 6px 24px rgba(79,70,229,.35)}
.cta-main:hover{background:var(--indigo-d);transform:translateY(-2px);box-shadow:0 12px 32px rgba(79,70,229,.45)}
.cta-play{display:inline-flex;align-items:center;gap:9px;background:#fff;color:var(--text);padding:14px 24px;border-radius:10px;font-weight:700;font-size:15px;border:2px solid var(--border);transition:all .28s;box-shadow:0 2px 10px rgba(0,0,0,.07)}
.cta-play:hover{border-color:var(--indigo);color:var(--indigo);transform:translateY(-2px)}
.cta-wa{display:inline-flex;align-items:center;gap:8px;color:#059669;font-size:14px;font-weight:600;padding:12px 18px;border-radius:10px;border:2px solid #D1FAE5;background:#F0FDF4;transition:all .2s}
.cta-wa:hover{background:#D1FAE5;transform:translateY(-2px)}
.hero-trust{display:flex;align-items:center;gap:16px;padding:16px 20px;background:#F9FAFB;border-radius:12px;border:1.5px solid var(--border);width:fit-content}
.trust-avatars{display:flex;margin-right:4px}
.ta{width:32px;height:32px;border-radius:50%;border:2px solid #fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;margin-left:-8px}
.ta:first-child{margin-left:0}
.trust-text p{font-size:14px;font-weight:700;color:var(--text)}
.trust-text span{font-size:12px;color:var(--muted)}
.stars{color:#F59E0B;font-size:11px;letter-spacing:1px}

/* ─── HERO RIGHT (PHONE) ─── */
.hero-right{display:flex;justify-content:center;align-items:center;position:relative}
.phone-mockup{position:relative;z-index:2;display:flex;justify-content:center}
.phone-mockup img{width:auto;max-width:100%;max-height:65vh;height:auto;display:block;filter:drop-shadow(0 30px 60px rgba(79,70,229,.2))}

/* Floating cards */
.fcard{position:absolute;background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(0,0,0,.12);padding:14px 18px;z-index:10;border:1px solid rgba(0,0,0,.05)}
.fcard.fc1{top:50px;right:-30px;animation:float 5s ease-in-out infinite}
.fcard.fc2{bottom:90px;left:-40px;animation:float 5s ease-in-out infinite 2.5s}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-12px)}}
.fc-icon{font-size:20px;margin-bottom:6px}
.fc-val{font-size:18px;font-weight:900;color:var(--text)}
.fc-lbl{font-size:11px;color:var(--muted);font-weight:500}
.fc-badge{display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;padding:3px 9px;border-radius:999px;margin-top:6px;width:fit-content}

/* BG deco */
.hero-circle{position:absolute;border-radius:50%;opacity:.06}
.hc1{width:500px;height:500px;background:var(--indigo);top:-150px;right:-100px}
.hc2{width:300px;height:300px;background:var(--amber);bottom:0;right:20%}

/* ─── SOCIAL PROOF BAR ─── */
.proof-bar{background:var(--bg);border-top:1px solid var(--border);border-bottom:1px solid var(--border);padding:36px 5%}
.proof-inner{max-width:1000px;margin:0 auto;display:flex;align-items:center;justify-content:space-around;gap:32px;flex-wrap:wrap;text-align:center}
.proof-item .pn{font-size:32px;font-weight:900;color:var(--indigo)}
.proof-item .pl{font-size:13px;color:var(--muted);margin-top:4px;font-weight:500}

/* ─── SECTION COMMON ─── */
.section{padding:96px 5%}
.section.alt{background:var(--bg)}
.section.dark{background:#111827}
.sec-wrap{max-width:1200px;margin:0 auto}
.sec-label{display:inline-flex;align-items:center;gap:7px;font-size:11px;font-weight:700;color:var(--indigo);background:var(--indigo-l);padding:5px 14px;border-radius:999px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:14px}
.sec-title{font-size:clamp(28px,3.5vw,44px);font-weight:900;color:var(--text);line-height:1.12;letter-spacing:-.6px;margin-bottom:14px}
.sec-sub{font-size:16px;color:var(--muted);line-height:1.78;max-width:540px}
.center{text-align:center}
.center .sec-sub{margin:0 auto}
.dark .sec-title{color:#fff}
.dark .sec-sub{color:rgba(255,255,255,.55)}

/* REVEAL */
.rv{opacity:0;transform:translateY(32px);transition:opacity .65s cubic-bezier(.16,1,.3,1),transform .65s cubic-bezier(.16,1,.3,1)}
.rv.on{opacity:1;transform:none}
.rv-l{opacity:0;transform:translateX(-32px);transition:opacity .65s cubic-bezier(.16,1,.3,1),transform .65s cubic-bezier(.16,1,.3,1)}
.rv-l.on{opacity:1;transform:none}
.rv-r{opacity:0;transform:translateX(32px);transition:opacity .65s cubic-bezier(.16,1,.3,1),transform .65s cubic-bezier(.16,1,.3,1)}
.rv-r.on{opacity:1;transform:none}
.d1{transition-delay:.08s}.d2{transition-delay:.16s}.d3{transition-delay:.24s}
.d4{transition-delay:.32s}.d5{transition-delay:.40s}.d6{transition-delay:.48s}

/* ─── FEATURES ─── */
.feat-list{display:flex;flex-direction:column;gap:80px;margin-top:72px}
.feat-row{display:grid;grid-template-columns:1fr 1fr;gap:80px;align-items:center}
.feat-row.flip{direction:rtl}
.feat-row.flip > *{direction:ltr}
.feat-screen{position:relative;display:flex;justify-content:center}
.feat-phone{width:100%;max-width:340px;overflow:hidden;border-radius:20px}
.feat-phone img{width:100%;height:auto;display:block;max-height:580px;object-fit:cover;object-position:top center;filter:drop-shadow(0 24px 60px rgba(0,0,0,.18))}
.feat-glow{position:absolute;bottom:-20px;left:50%;transform:translateX(-50%);width:180px;height:40px;border-radius:50%;filter:blur(18px);opacity:.4}
.feat-info{padding:8px 0}
.feat-icon-wrap{width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:20px}
.feat-h{font-size:28px;font-weight:900;color:var(--text);letter-spacing:-.4px;margin-bottom:12px;line-height:1.2}
.feat-p{font-size:15.5px;color:var(--muted);line-height:1.82;margin-bottom:24px}
.feat-chips{display:flex;flex-wrap:wrap;gap:8px}
.chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;padding:7px 14px;border-radius:999px;border:1.5px solid var(--border);color:var(--muted);background:var(--white)}
.chip i{font-size:10px}

/* ─── WHY CARDS ─── */
.why-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:56px}
.why-card{background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:30px;transition:all .3s;cursor:default}
.why-card:hover{border-color:var(--indigo);transform:translateY(-5px);box-shadow:0 20px 50px rgba(79,70,229,.1)}
.why-n{font-size:13px;font-weight:700;color:var(--indigo);background:var(--indigo-l);display:inline-block;padding:4px 12px;border-radius:999px;margin-bottom:14px}
.why-card h4{font-size:17px;font-weight:800;color:var(--text);margin-bottom:9px}
.why-card p{font-size:14px;color:var(--muted);line-height:1.75}

/* ─── SCREENSHOTS ─── */
.ss-wrap{overflow:hidden;margin-top:56px;position:relative}
.ss-track{display:flex;gap:16px;overflow-x:auto;padding:12px 5% 36px;scrollbar-width:none;cursor:grab;justify-content:center}
.ss-track::-webkit-scrollbar{display:none}
.ss-track:active{cursor:grabbing}
.ss-item{flex-shrink:0;width:220px;max-height:400px;border-radius:20px;overflow:hidden;border:1.5px solid var(--border);box-shadow:0 12px 40px rgba(0,0,0,.1);transition:all .3s;background:#F3F4F6}
.ss-item:hover{transform:translateY(-8px) scale(1.02);box-shadow:0 24px 60px rgba(0,0,0,.18);border-color:rgba(79,70,229,.3)}
.ss-item img{width:100%;height:auto;max-height:400px;object-fit:cover;object-position:top;display:block}

/* ─── TESTIMONIALS ─── */
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-top:56px}
.tcard{background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:28px;transition:all .3s;position:relative}
.tcard:hover{border-color:var(--indigo);transform:translateY(-4px);box-shadow:0 16px 44px rgba(79,70,229,.1)}
.tcard-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.tc-stars{color:#F59E0B;font-size:14px;letter-spacing:2px}
.tc-logo{font-size:11px;font-weight:700;color:var(--indigo);background:var(--indigo-l);padding:4px 10px;border-radius:999px}
.tcard-q{font-size:14.5px;color:#374151;line-height:1.82;margin-bottom:22px}
.tcard-user{display:flex;align-items:center;gap:12px;padding-top:18px;border-top:1px solid var(--border)}
.tc-av{width:44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:17px;color:#fff;flex-shrink:0}
.tc-name{font-weight:700;font-size:14px;color:var(--text)}
.tc-role{font-size:12.5px;color:var(--muted);margin-top:2px}

/* ─── HOW IT WORKS ─── */
.how-steps{display:grid;grid-template-columns:repeat(3,1fr);gap:32px;margin-top:60px;position:relative}
.how-steps::before{content:'';position:absolute;top:32px;left:16.5%;right:16.5%;height:2px;background:linear-gradient(90deg,var(--indigo),var(--amber));z-index:0;opacity:.2}
.how-step{text-align:center;position:relative;z-index:1}
.how-num{width:64px;height:64px;border-radius:50%;background:var(--indigo);color:#fff;font-size:22px;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto 22px;box-shadow:0 8px 24px rgba(79,70,229,.3)}
.how-step h4{font-size:18px;font-weight:800;color:var(--text);margin-bottom:9px}
.how-step p{font-size:14px;color:var(--muted);line-height:1.75}

/* ─── DOWNLOAD BANNER ─── */
.dl-banner{background:linear-gradient(135deg,#4F46E5 0%,#7C3AED 50%,#4338CA 100%);padding:80px 5%;position:relative;overflow:hidden}
.dl-banner::before{content:'';position:absolute;top:-50%;right:-10%;width:600px;height:600px;background:rgba(255,255,255,.04);border-radius:50%}
.dl-banner::after{content:'';position:absolute;bottom:-60%;left:-5%;width:500px;height:500px;background:rgba(255,255,255,.03);border-radius:50%}
.dl-inner{max-width:900px;margin:0 auto;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;position:relative;z-index:1}
.dl-h{font-size:clamp(28px,3.5vw,44px);font-weight:900;color:#fff;line-height:1.1;letter-spacing:-.6px;margin-bottom:12px}
.dl-p{font-size:16px;color:rgba(255,255,255,.65);line-height:1.75;margin-bottom:32px}
.dl-btns{display:flex;gap:12px;flex-wrap:wrap}
.cta-play{display:inline-flex;align-items:center;gap:9px;background:#fff;color:#059669;padding:13px 22px;border-radius:10px;font-weight:700;font-size:14.5px;border:1.5px solid rgba(5,150,105,.2);transition:all .25s}
.cta-play:hover{background:#ECFDF5;transform:translateY(-2px);box-shadow:0 8px 24px rgba(5,150,105,.15)}
.cta-soon{display:inline-flex;align-items:center;gap:9px;background:#F9FAFB;color:#9CA3AF;padding:13px 22px;border-radius:10px;font-weight:700;font-size:14.5px;border:1.5px solid #E5E7EB;cursor:default}
.soon-badge{background:#F59E0B;color:#fff;font-size:10px;font-weight:800;padding:2px 8px;border-radius:999px;white-space:nowrap}
.cta-win{display:inline-flex;align-items:center;gap:9px;background:#0078D4;color:#fff;padding:13px 22px;border-radius:10px;font-weight:700;font-size:14.5px;transition:all .25s}
.cta-win:hover{background:#106EBE;transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,120,212,.25)}
.dl-btn-g{display:inline-flex;align-items:center;gap:9px;background:rgba(255,255,255,.12);color:#fff;padding:14px 22px;border-radius:10px;font-weight:700;font-size:14px;border:1.5px solid rgba(255,255,255,.2);transition:all .25s}
.dl-btn-g:hover{background:rgba(255,255,255,.2);transform:translateY(-2px)}
.dl-btn-w{display:inline-flex;align-items:center;gap:9px;background:#fff;color:#4F46E5;padding:14px 22px;border-radius:10px;font-weight:700;font-size:14px;transition:all .25s;box-shadow:0 4px 16px rgba(0,0,0,.15)}
.dl-btn-w:hover{background:#f8f8ff;transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.2)}
.dl-right{display:flex;justify-content:center}
.dl-phone{width:260px;border-radius:20px;overflow:hidden;box-shadow:0 30px 80px rgba(0,0,0,.35);transform:rotate(3deg)}
.dl-phone img{width:100%;height:auto;display:block}

/* ─── FAQ ─── */
.faq-list{max-width:720px;margin:52px auto 0}
.faq-item{border-bottom:1.5px solid var(--border)}
.faq-q{width:100%;background:none;border:none;padding:22px 0;display:flex;justify-content:space-between;align-items:center;font-size:15.5px;font-weight:700;color:var(--text);cursor:pointer;gap:16px;text-align:left;transition:color .2s}
.faq-q:hover{color:var(--indigo)}
.faq-q .icon{width:28px;height:28px;border-radius:50%;background:var(--bg);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:all .3s}
.faq-item.open .faq-q{color:var(--indigo)}
.faq-item.open .faq-q .icon{background:var(--indigo);border-color:var(--indigo);transform:rotate(45deg)}
.faq-item.open .faq-q .icon i{color:#fff}
.faq-a{max-height:0;overflow:hidden;transition:max-height .4s cubic-bezier(.16,1,.3,1)}
.faq-item.open .faq-a{max-height:160px}
.faq-a p{font-size:14.5px;color:var(--muted);line-height:1.82;padding-bottom:22px}

/* ─── CONTACT FORM ─── */
.form-sec{background:var(--bg);padding:96px 5%}
.form-box{max-width:580px;margin:0 auto;background:#fff;border:1.5px solid var(--border);border-radius:24px;padding:48px;box-shadow:0 20px 60px rgba(0,0,0,.06)}
.form-box h3{font-size:24px;font-weight:900;color:var(--text);margin-bottom:6px;text-align:center}
.form-sub{font-size:14px;color:var(--muted);text-align:center;margin-bottom:32px}
.field{margin-bottom:14px}
.field input,.field textarea{width:100%;padding:13px 16px;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;font-size:14.5px;font-family:'Inter',sans-serif;color:var(--text);outline:none;transition:all .2s}
.field input::placeholder,.field textarea::placeholder{color:var(--light)}
.field input:focus,.field textarea:focus{border-color:var(--indigo);background:#fff;box-shadow:0 0 0 4px rgba(79,70,229,.06)}
.field textarea{height:80px;resize:none}
.btn-send{width:100%;padding:14px;background:var(--indigo);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:800;cursor:pointer;font-family:'Inter',sans-serif;transition:all .25s;display:flex;align-items:center;justify-content:center;gap:9px;margin-top:6px}
.btn-send:hover{background:var(--indigo-d);transform:translateY(-2px);box-shadow:0 8px 24px rgba(79,70,229,.3)}
.btn-send:disabled{opacity:.6;cursor:not-allowed;transform:none}
.fm-result{display:none;padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;text-align:center;margin-bottom:14px;border:1.5px solid}
.field-err{font-size:12px;color:var(--red);margin-top:4px;display:none}
.field-err.show{display:block}
.honey{display:none}

/* ─── VIDEO SECTION ─── */
.video-sec{background:#fff;padding:96px 5%;border-top:1px solid var(--border)}
.video-wrap{max-width:900px;margin:0 auto;position:relative;padding-bottom:56.25%;height:0;border-radius:20px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.15)}
.video-wrap iframe{position:absolute;top:0;left:0;width:100%;height:100%;border:none}

/* ─── FOOTER ─── */
.footer{background:#111827;padding:64px 5% 32px}
.footer-top{max-width:1200px;margin:0 auto;display:grid;grid-template-columns:2.2fr 1fr 1fr 1fr;gap:40px;padding-bottom:48px;border-bottom:1px solid rgba(255,255,255,.07)}
.ft-brand img{width:36px;border-radius:10px;margin-bottom:12px}
.ft-brand p{font-size:13px;color:rgba(255,255,255,.35);line-height:1.75;max-width:200px;margin-bottom:18px}
.ft-brand-name{font-size:18px;font-weight:900;color:#fff;margin-bottom:12px}
.soc-links{display:flex;gap:8px}
.soc-link{width:36px;height:36px;border-radius:9px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.4);font-size:13px;transition:all .2s}
.soc-link:hover{background:var(--indigo);color:#fff;border-color:var(--indigo)}
.ft-col h5{font-size:11px;font-weight:700;color:rgba(255,255,255,.3);text-transform:uppercase;letter-spacing:.1em;margin-bottom:18px}
.ft-col a{display:block;font-size:13.5px;color:rgba(255,255,255,.45);margin-bottom:11px;transition:color .2s}
.ft-col a:hover{color:#fff}
.footer-bottom{max-width:1200px;margin:28px auto 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px}
.footer-bottom p{font-size:13px;color:rgba(255,255,255,.2)}

/* ─── MOBILE MENU ─── */
.hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px;z-index:9500}
.hamburger span{display:block;width:24px;height:2.5px;background:var(--text);border-radius:2px;transition:all .3s}
.hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
.hamburger.active span:nth-child(2){opacity:0}
.hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(5px,-5px)}
.mobile-menu{display:none;position:fixed;top:70px;left:0;right:0;background:rgba(255,255,255,.98);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:20px 5%;flex-direction:column;gap:16px;z-index:8999;box-shadow:0 10px 40px rgba(0,0,0,.08)}
.mobile-menu a{font-size:15px;font-weight:600;color:var(--text);padding:10px 0;border-bottom:1px solid var(--border);transition:color .2s}
.mobile-menu a:last-child{border-bottom:none}
.mobile-menu a:hover{color:var(--indigo)}
.mobile-menu .lang-switch{display:flex;align-items:center;gap:8px;padding:10px 0}
.mobile-menu .lang-switch select{width:100%}
.mobile-menu.open{display:flex}

/* ─── COOKIE BANNER ─── */
.cookie-banner{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1.5px solid var(--border);padding:16px 5%;display:flex;align-items:center;justify-content:space-between;gap:16px;z-index:9999;box-shadow:0 -4px 24px rgba(0,0,0,.08);transform:translateY(100%);transition:transform .4s cubic-bezier(.16,1,.3,1)}
.cookie-banner.show{transform:translateY(0)}
.cookie-banner p{font-size:13.5px;color:var(--muted);line-height:1.6;max-width:600px}
.cookie-banner p a{color:var(--indigo);font-weight:600;text-decoration:underline}
.cookie-banner button{padding:10px 20px;border-radius:8px;font-size:13px;font-weight:700;font-family:'Inter',sans-serif;cursor:pointer;transition:all .2s;border:none}
.cookie-banner .btn-accept{background:var(--indigo);color:#fff}
.cookie-banner .btn-accept:hover{background:var(--indigo-d)}

/* ─── SCROLL TO TOP ─── */
.scroll-top{position:fixed;bottom:24px;right:24px;width:44px;height:44px;border-radius:50%;background:var(--indigo);color:#fff;border:none;display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;opacity:0;visibility:hidden;transform:translateY(10px);transition:all .3s;z-index:8000;box-shadow:0 4px 14px rgba(79,70,229,.3)}
.scroll-top.visible{opacity:1;visibility:visible;transform:translateY(0)}
.scroll-top:hover{background:var(--indigo-d);transform:translateY(-2px)}

/* ─── RESPONSIVE ─── */
@media(max-width:1024px){
  .hero-inner{grid-template-columns:1fr 1fr;gap:40px}
  .feat-row{grid-template-columns:1fr 1fr;gap:40px}
  .dl-inner{grid-template-columns:1fr;text-align:center}
  .dl-btns{justify-content:center}
  .dl-right{display:none}
}
@media(max-width:768px){
  .hero-inner{grid-template-columns:1fr;text-align:center}
  .hero-left{align-items:center}
  .hero-right{display:none}
  .why-grid,.testi-grid,.how-steps{grid-template-columns:1fr 1fr}
  .proof-inner{gap:20px}
  .feat-row,.feat-row.flip{grid-template-columns:1fr;direction:ltr}
  .feat-screen{order:-1}
  .footer-top{grid-template-columns:1fr 1fr}
  .how-steps::before{display:none}
}
@media(max-width:540px){
  .nav-links{display:none}
  .hamburger{display:flex}
  .why-grid,.testi-grid,.how-steps{grid-template-columns:1fr}
  .hero-cta{flex-direction:column;align-items:center;width:100%}
  .cta-main,.cta-play,.cta-wa{width:100%;justify-content:center}
  .footer-top{grid-template-columns:1fr}
  .nav{padding:0 20px}
  .section,.form-sec,.dl-banner,.proof-bar{padding-left:20px;padding-right:20px}
}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav" id="nav">
  <a href="#" class="logo"><img src="images/icon.png" alt="Binest" width="36" height="36" loading="lazy"><span class="logo-name">Bi<span>nest</span></span></a>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#screenshots">Screenshots</a>
    <a href="#how">How it Works</a>
    <a href="#faq">FAQ</a>
  </div>
  <div class="nav-actions">
    <?php if($wa): ?><a href="<?= $wa ?>" target="_blank" class="btn-ghost"><i class="fab fa-whatsapp"></i> WhatsApp</a><?php endif; ?>
    <?php if($psMode==='link' && !empty($ps)): ?><a href="<?= htmlspecialchars($ps) ?>" target="_blank" class="btn-primary"><i class="fab fa-google-play"></i> Get on Play Store</a>
    <?php elseif($showApk): ?><a href="apk/binest.apk" download class="btn-primary"><i class="fas fa-download"></i> Free Download</a><?php endif; ?>
    <div class="lang-switch">
      <select id="langSelect" onchange="changeLang(this.value)">
        <option value="en">EN</option>
        <option value="hi">HI</option>
        <option value="mr">MR</option>
        <option value="gu">GU</option>
      </select>
    </div>
    <div class="hamburger" id="hamburger" aria-label="Open menu"><span></span><span></span><span></span></div>
  </div>
</nav>
<div class="mobile-menu" id="mobileMenu">
  <a href="#features">Features</a>
  <a href="#screenshots">Screenshots</a>
  <a href="#how">How it Works</a>
  <a href="#faq">FAQ</a>
  <a href="#contact">Contact</a>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hero-circle hc1"></div>
  <div class="hero-circle hc2"></div>
  <div class="hero-inner">
    <div class="hero-left">
      <div class="hero-tag rv"><div class="tag-dot"></div>v<?= htmlspecialchars($apkV) ?> — Free to Get Started</div>
      <h1 class="hero-h1 rv d1">Run your business<br><em>smarter</em>, not harder.</h1>
      <p class="hero-desc rv d2">Billing, inventory, customers, expenses & reports — one simple app built specifically for Indian small businesses. Sign up and start managing your shop in minutes.</p>
      <div class="hero-cta rv d3">
        <?php if($showApk): ?>
        <a href="apk/binest.apk" download class="cta-main"><i class="fas fa-download"></i> Download Free APK</a>
        <?php endif; ?>
        <?php if($psMode==='link' && !empty($ps)): ?>
        <a href="<?= htmlspecialchars($ps) ?>" target="_blank" class="<?= $showApk ? 'cta-play' : 'cta-main' ?>"><i class="fab fa-google-play"></i> Play Store</a>
        <?php elseif($psMode==='coming_soon'): ?>
        <span class="<?= $showApk ? 'cta-soon' : 'cta-main' ?>" style="<?= $showApk ? '' : 'background:#fff;color:var(--indigo);cursor:default' ?>"><i class="fab fa-google-play"></i> Play Store <span class="soon-badge">Coming Soon</span></span>
        <?php endif; ?>
        <?php if($showWin && !empty($winUrl)): ?>
        <a href="<?= htmlspecialchars($winUrl) ?>" target="_blank" class="cta-win"><i class="fab fa-windows"></i> Windows</a>
        <?php endif; ?>
        <?php if($wa): ?><a href="<?= $wa ?>" target="_blank" class="cta-wa"><i class="fab fa-whatsapp"></i> Chat with Us</a><?php endif; ?>
      </div>
      <div class="hero-trust rv d4">
        <div class="trust-avatars">
          <div class="ta" style="background:#4F46E5">R</div>
          <div class="ta" style="background:#059669">S</div>
          <div class="ta" style="background:#D97706">A</div>
          <div class="ta" style="background:#7C3AED">M</div>
        </div>
        <div class="trust-text">
          <div class="stars">★★★★★</div>
          <p>Trusted by Indian shopkeepers</p>
          <span>Kirana · Medical · Mobile Shops · Clothing</span>
        </div>
      </div>
    </div>
    <div class="hero-right rv-r d1">
      <div class="phone-mockup">
        <img src="images/screen-home.png" alt="Binest Dashboard" width="320" height="640" loading="lazy">
        <div class="fcard fc1">
          <div class="fc-icon">📊</div>
          <div class="fc-val">₹24,800</div>
          <div class="fc-lbl">Today's Revenue</div>
          <div class="fc-badge" style="background:#ECFDF5;color:#059669"><i class="fas fa-arrow-up"></i> +18% vs yesterday</div>
        </div>
        <div class="fcard fc2">
          <div class="fc-icon">📦</div>
          <div class="fc-val">47 Bills</div>
          <div class="fc-lbl">Created today</div>
          <div class="fc-badge" style="background:#EEF2FF;color:#4F46E5"><i class="fas fa-bolt"></i> Auto-saved</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SOCIAL PROOF BAR -->
<div class="proof-bar">
  <div class="proof-inner">
    <div class="proof-item rv"><div class="pn">Free</div><div class="pl">Start at Zero Cost</div></div>
    <div class="proof-item rv d1"><div class="pn">Fast</div><div class="pl">Bill in 30 Seconds</div></div>
    <div class="proof-item rv d2"><div class="pn">2</div><div class="pl">Indian Languages</div></div>
    <div class="proof-item rv d3"><div class="pn">Sync</div><div class="pl">Data Synced to Server</div></div>
    <div class="proof-item rv d4"><div class="pn">Safe</div><div class="pl">Secure Data Storage</div></div>
  </div>
</div>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="sec-wrap">
    <div class="center rv">
      <div class="sec-label"><i class="fas fa-bolt"></i> Features</div>
      <h2 class="sec-title">Everything you need in one place</h2>
      <p class="sec-sub">Designed for Indian shopkeepers — simple enough to learn in 5 minutes, powerful enough to run your entire business.</p>
    </div>
    <div class="feat-list">

      <!-- Feature 1 -->
      <div class="feat-row">
        <div class="feat-screen rv-l">
          <div class="feat-phone"><img src="images/screen-products.png" alt="Billing" width="340" height="580" loading="lazy"></div>
          <div class="feat-glow" style="background:#4F46E5"></div>
        </div>
        <div class="feat-info rv-r">
          <div class="feat-icon-wrap" style="background:#EEF2FF"><i class="fas fa-receipt" style="color:#4F46E5;font-size:24px"></i></div>
          <h3 class="feat-h">Smart Billing in Seconds</h3>
          <p class="feat-p">Create professional bills in under 30 seconds. Select customer, add products from inventory or manually, apply discount & tax, and share via PDF. Cash, UPI, Card, and Due payment modes supported.</p>
          <div class="feat-chips">
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Tax & Discount</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> PDF Share</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Due Payments</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Multiple Payments</span>
          </div>
        </div>
      </div>

      <!-- Feature 2 -->
      <div class="feat-row flip">
        <div class="feat-screen rv-r">
          <div class="feat-phone"><img src="images/screen-inventory.png" alt="Inventory" width="340" height="580" loading="lazy"></div>
          <div class="feat-glow" style="background:#059669"></div>
        </div>
        <div class="feat-info rv-l">
          <div class="feat-icon-wrap" style="background:#ECFDF5"><i class="fas fa-boxes-stacked" style="color:#059669;font-size:24px"></i></div>
          <h3 class="feat-h">Inventory That Manages Itself</h3>
          <p class="feat-p">Stock automatically deducts on every sale. Get instant red alerts before you run out. Manage all units — kg, litre, piece, box — in one place. Never lose a sale to stockout again.</p>
          <div class="feat-chips">
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Auto Deduction</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Low Stock Alert</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> All Units</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Category-wise</span>
          </div>
        </div>
      </div>

      <!-- Feature 3 -->
      <div class="feat-row">
        <div class="feat-screen rv-l">
          <div class="feat-phone"><img src="images/screen-reports.png" alt="Reports" width="340" height="580" loading="lazy"></div>
          <div class="feat-glow" style="background:#7C3AED"></div>
        </div>
        <div class="feat-info rv-r">
          <div class="feat-icon-wrap" style="background:#F5F3FF"><i class="fas fa-chart-bar" style="color:#7C3AED;font-size:24px"></i></div>
          <h3 class="feat-h">Reports That Tell Your Story</h3>
          <p class="feat-p">See how your business is performing with visual bar charts, daily and monthly summaries, best-selling products, and clear profit vs loss. All in one glance — no accountant needed.</p>
          <div class="feat-chips">
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> 7-Day Chart</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Best Sellers</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Profit & Loss</span>
            <span class="chip"><i class="fas fa-check" style="color:#059669"></i> Export & Share</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- WHY BINEST -->
<section class="section alt">
  <div class="sec-wrap">
    <div class="center rv">
      <div class="sec-label"><i class="fas fa-heart"></i> Why Binest</div>
      <h2 class="sec-title">Built for real Indian businesses</h2>
      <p class="sec-sub">We didn't build a generic app and add "India" to the name. Binest was designed from scratch for Indian shopkeepers.</p>
    </div>
    <div class="why-grid">
      <div class="why-card rv d1"><div class="why-n">Free Start</div><h4>Start at Zero Cost</h4><p>Download and start using for free. Upgrade when your business grows — no credit card required to get started.</p></div>
      <div class="why-card rv d2"><div class="why-n">Data Sync</div><h4>Auto-Sync to Server</h4><p>Your data is securely stored on the server. Access your business records from anywhere, on any device with your login.</p></div>
      <div class="why-card rv d3"><div class="why-n">2 Languages</div><h4>English & Hindi</h4><p>Full app interface available in English and Hindi. Switch instantly from app settings without losing any data.</p></div>
      <div class="why-card rv d4"><div class="why-n">5 Minutes</div><h4>Setup in 5 Minutes</h4><p>No training, no complexity. Add your first product and create your first bill within 5 minutes of installing.</p></div>
      <div class="why-card rv d5"><div class="why-n">Due Tracking</div><h4>Udhari Management</h4><p>Track credit sales and customer dues in one place. Record partial payments, view history, and send reminders.</p></div>
      <div class="why-card rv d6"><div class="why-n">All Devices</div><h4>Any Android Phone</h4><p>Works on any Android smartphone — from basic phones to flagship devices. No high-end device needed.</p></div>
    </div>
  </div>
</section>

<!-- SCREENSHOTS -->
<section class="section" id="screenshots">
  <div class="sec-wrap">
    <div class="center rv">
      <div class="sec-label"><i class="fas fa-mobile-screen"></i> Real App UI</div>
      <h2 class="sec-title">Real screenshots. Not mockups.</h2>
      <p class="sec-sub">Every screen below is directly from the Binest app. What you see is exactly what you get.</p>
    </div>
  </div>
  <div class="ss-wrap rv d1">
    <div class="ss-track">
      <div class="ss-item"><img src="images/screen-home.png" alt="Dashboard" width="220" height="400" loading="lazy"></div>
      <div class="ss-item"><img src="images/screen-inventory.png" alt="Inventory" width="220" height="400" loading="lazy"></div>
      <div class="ss-item"><img src="images/screen-customers.png" alt="Customers" width="220" height="400" loading="lazy"></div>
      <div class="ss-item"><img src="images/screen-products.png" alt="Products" width="220" height="400" loading="lazy"></div>
      <div class="ss-item"><img src="images/screen-reports.png" alt="Reports" width="220" height="400" loading="lazy"></div>
      <div class="ss-item"><img src="images/screen-expenses.png" alt="Expenses" width="220" height="400" loading="lazy"></div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section alt" id="how">
  <div class="sec-wrap">
    <div class="center rv">
      <div class="sec-label"><i class="fas fa-rocket"></i> Getting Started</div>
      <h2 class="sec-title">Up and running in 3 steps</h2>
      <p class="sec-sub">No onboarding calls, no setup fees. Just download and start managing your business.</p>
    </div>
    <div class="how-steps">
      <div class="how-step rv d1">
        <div class="how-num">1</div>
        <h4>Download Binest</h4>
        <p>Get the app from Google Play Store. Install in under a minute on any Android phone.</p>
      </div>
      <div class="how-step rv d2">
        <div class="how-num">2</div>
        <h4>Add Your Business</h4>
        <p>Enter your shop name, add products with prices and stock. Set up takes less than 5 minutes.</p>
      </div>
      <div class="how-step rv d3">
        <div class="how-num">3</div>
        <h4>Start & Grow</h4>
        <p>Create bills, track inventory, view daily reports. Make smarter business decisions every day.</p>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section">
  <div class="sec-wrap">
    <div class="center rv">
      <div class="sec-label"><i class="fas fa-quote-left"></i> Reviews</div>
      <h2 class="sec-title">Business owners love Binest</h2>
      <p class="sec-sub">Real reviews from real shop owners using Binest every day.</p>
    </div>
    <div class="testi-grid">
      <div class="tcard rv d1">
        <div class="tcard-top"><div class="tc-stars">★★★★★</div><div class="tc-logo">Kirana Store</div></div>
        <p class="tcard-q">"Pehle sab haath se likhta tha, bahut time lagta tha. Ab ek touch mein professional bill ban jata hai aur WhatsApp pe bhi share ho jata hai. Business bahut smooth ho gaya!"</p>
        <div class="tcard-user"><div class="tc-av" style="background:linear-gradient(135deg,#4F46E5,#7C3AED)">R</div><div><div class="tc-name">Ramesh Patel</div><div class="tc-role">Kirana Store · Surat, Gujarat</div></div></div>
      </div>
      <div class="tcard rv d3">
        <div class="tcard-top"><div class="tc-stars">★★★★★</div><div class="tc-logo">Medical Shop</div></div>
        <p class="tcard-q">"Stock alert feature ne meri life badal di! Kabhi bhi koi medicine khatam nahi hoti ab. Reports se pata chalta hai kaunsa product best sell hota hai. Profit clearly dikh raha hai!"</p>
        <div class="tcard-user"><div class="tc-av" style="background:linear-gradient(135deg,#059669,#047857)">S</div><div><div class="tc-name">Sunita Sharma</div><div class="tc-role">Medical Store · Pune, Maharashtra</div></div></div>
      </div>
      <div class="tcard rv d5">
        <div class="tcard-top"><div class="tc-stars">★★★★★</div><div class="tc-logo">Electronics</div></div>
        <p class="tcard-q">"Hindi mein bhi use kar sakta hoon — bahut acha! Daily reports se pata chalta hai kitna profit hua. Ab main data dekhke decision leta hoon, guess nahi karta. Business grow ho raha hai!"</p>
        <div class="tcard-user"><div class="tc-av" style="background:linear-gradient(135deg,#D97706,#B45309)">A</div><div><div class="tc-name">Arjun Mehta</div><div class="tc-role">Electronics Shop · Mumbai, Maharashtra</div></div></div>
      </div>
    </div>
  </div>
</section>

<!-- DOWNLOAD BANNER -->
<div class="dl-banner">
  <div class="dl-inner">
    <div class="rv">
      <h2 class="dl-h">Ready to grow your business?</h2>
      <p class="dl-p">Start managing your business smarter with Binest. Free to get started — upgrade as you grow.</p>
      <div class="dl-btns">
        <?php if($showApk): ?>
        <a href="apk/binest.apk" download class="dl-btn-w"><i class="fas fa-download"></i> Download Free APK</a>
        <?php endif; ?>
        <?php if($psMode==='link' && !empty($ps)): ?><a href="<?= htmlspecialchars($ps) ?>" target="_blank" class="<?= $showApk ? 'dl-btn-g' : 'dl-btn-w' ?>"><i class="fab fa-google-play"></i> Play Store</a>
        <?php elseif($psMode==='coming_soon'): ?><span class="<?= $showApk ? 'dl-btn-g' : 'dl-btn-w' ?>" style="<?= $showApk ? 'opacity:.65;cursor:default' : 'cursor:default' ?>"><i class="fab fa-google-play"></i> Play Store &nbsp;<span style="background:rgba(255,255,255,.25);padding:2px 8px;border-radius:999px;font-size:11px">Coming Soon</span></span><?php endif; ?>
        <?php if($wa): ?><a href="<?= $wa ?>" target="_blank" class="dl-btn-g"><i class="fab fa-whatsapp"></i> WhatsApp Us</a><?php endif; ?>
        <?php if($showWin && !empty($winUrl)): ?><a href="<?= htmlspecialchars($winUrl) ?>" target="_blank" class="dl-btn-g"><i class="fab fa-windows"></i> Windows</a><?php endif; ?>
      </div>
    </div>
    <div class="dl-right rv-r">
      <div class="dl-phone"><img src="images/screen-home.png" alt="Binest App" width="260" height="520" loading="lazy"></div>
    </div>
  </div>
</div>

<?php if(!empty($vid)): ?>
<!-- VIDEO DEMO -->
<section class="video-sec" id="video">
  <div class="sec-wrap center">
    <div class="sec-label rv"><i class="fas fa-play-circle"></i> Watch Demo</div>
    <h2 class="sec-title rv">See Binest in action</h2>
    <p class="sec-sub rv" style="margin-bottom:40px">A quick walkthrough of how Binest helps you manage your business daily.</p>
    <div class="video-wrap rv">
      <iframe src="<?= htmlspecialchars($vid) ?>" title="Binest Demo Video" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- FAQ -->
<section class="section" id="faq">
  <div class="sec-wrap">
    <div class="center rv">
      <div class="sec-label"><i class="fas fa-circle-question"></i> FAQ</div>
      <h2 class="sec-title">Frequently asked questions</h2>
    </div>
    <div class="faq-list">
      <div class="faq-item"><button class="faq-q">Is Binest free to use?<div class="icon"><i class="fas fa-plus" style="font-size:11px;color:var(--muted)"></i></div></button><div class="faq-a"><p>Binest is free to download and start using. As your business grows, you can upgrade to unlock higher limits. No hidden fees — you only pay when you need more.</p></div></div>
      <div class="faq-item"><button class="faq-q">Do I need internet to use Binest?<div class="icon"><i class="fas fa-plus" style="font-size:11px;color:var(--muted)"></i></div></button><div class="faq-a"><p>Yes — Binest requires an internet connection to sync your data with the server. This keeps your records safe and accessible from any device. A stable connection ensures the best experience.</p></div></div>
      <div class="faq-item"><button class="faq-q">Which languages does Binest support?<div class="icon"><i class="fas fa-plus" style="font-size:11px;color:var(--muted)"></i></div></button><div class="faq-a"><p>Currently Binest supports English and Hindi. You can switch languages anytime from the app settings without losing any data. More languages coming soon.</p></div></div>
      <div class="faq-item"><button class="faq-q">Is there a limit on bills, products or customers?<div class="icon"><i class="fas fa-plus" style="font-size:11px;color:var(--muted)"></i></div></button><div class="faq-a"><p>The free version comes with generous limits to get you started. As your business grows, you can upgrade to unlock unlimited bills, products, and customers.</p></div></div>
      <div class="faq-item"><button class="faq-q">Can I share bills on WhatsApp?<div class="icon"><i class="fas fa-plus" style="font-size:11px;color:var(--muted)"></i></div></button><div class="faq-a"><p>Yes! Every bill can be shared directly to WhatsApp or downloaded as a PDF with a single tap. Your customer receives a professional-looking bill instantly.</p></div></div>
      <div class="faq-item"><button class="faq-q">Is my business data safe?<div class="icon"><i class="fas fa-plus" style="font-size:11px;color:var(--muted)"></i></div></button><div class="faq-a"><p>Your data is stored securely on our server with encrypted connections. We never share, sell or access your business information without your permission.</p></div></div>
    </div>
  </div>
</section>

<!-- CONTACT FORM -->
<div class="form-sec" id="contact">
  <div class="sec-wrap">
    <div class="center rv" style="margin-bottom:40px">
      <div class="sec-label"><i class="fas fa-envelope"></i> Contact</div>
      <h2 class="sec-title">Get in touch with us</h2>
      <p class="sec-sub">Have a question or want early access? Fill the form and we'll reach you within 24 hours.</p>
    </div>
    <div class="form-box rv d2">
      <h3>Send Us a Message</h3>
      <p class="form-sub">We respond within 24 hours — usually much faster.</p>
      <div class="fm-result" id="fmResult"></div>
      <div class="field"><input type="text" id="fmName" placeholder="Your Full Name *"><div class="field-err" id="errName">Please enter your name.</div></div>
      <div class="field"><input type="text" id="fmContact" placeholder="Phone Number or Email *"><div class="field-err" id="errContact">Please enter a valid phone number or email.</div></div>
      <div class="field"><textarea id="fmNote" placeholder="Tell us about your business (optional)"></textarea></div>
      <div class="field honey"><input type="text" id="fmWebsite" placeholder="Website"></div>
      <button class="btn-send" id="fmBtn" onclick="sendForm()"><i class="fas fa-paper-plane"></i> Send Message</button>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-top">
    <div class="ft-brand">
      <img src="images/icon.png" alt="Binest" width="36" height="36" loading="lazy">
      <div class="ft-brand-name">Binest</div>
      <p>Smart business management for every Indian small business. Free, fast and always synced.</p>
      <div class="soc-links">
        <?php if($ig): ?><a href="<?= htmlspecialchars($ig) ?>" target="_blank" class="soc-link"><i class="fab fa-instagram"></i></a><?php endif; ?>
        <?php if($fb): ?><a href="<?= htmlspecialchars($fb) ?>" target="_blank" class="soc-link"><i class="fab fa-facebook-f"></i></a><?php endif; ?>
        <?php if($yt): ?><a href="<?= htmlspecialchars($yt) ?>" target="_blank" class="soc-link"><i class="fab fa-youtube"></i></a><?php endif; ?>
        <?php if($wa): ?><a href="<?= $wa ?>" target="_blank" class="soc-link"><i class="fab fa-whatsapp"></i></a><?php endif; ?>
        <a href="mailto:<?= htmlspecialchars($mail) ?>" class="soc-link"><i class="fas fa-envelope"></i></a>
      </div>
    </div>
    <div class="ft-col"><h5>Product</h5><a href="#features">Features</a><a href="#screenshots">Screenshots</a><a href="#how">How it Works</a><a href="#contact">Get in Touch</a></div>
    <div class="ft-col"><h5>Support</h5><a href="#contact">Contact Us</a><?php if($wa): ?><a href="<?= $wa ?>" target="_blank">WhatsApp</a><?php endif; ?><a href="mailto:<?= htmlspecialchars($mail) ?>">Email Support</a></div>
    <div class="ft-col"><h5>Legal</h5><a href="privacy.php">Privacy Policy</a><a href="terms.php">Terms of Service</a><a href="delete-account.php">Delete Account</a></div>
  </div>
  <div class="footer-bottom">
    <p>© <?= date('Y') ?> Binest. All rights reserved.</p>
    <p>Made with ❤️ for Indian Businesses</p>
  </div>
<button class="scroll-top" id="scrollTop" aria-label="Scroll to top"><i class="fas fa-arrow-up"></i></button>

</footer>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Binest",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Android",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "INR"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.5",
    "ratingCount": "50"
  },
  "description": "Smart billing, inventory & business management app for Indian small businesses. Free download.",
  "screenshot": "<?= htmlspecialchars($cfg['lp_page_url'] ?? '') ?>/images/screen-home.png",
  "featureList": "Smart Billing, Inventory Management, Customer Management, Expense Tracking, Reports & Analytics, Data Sync, Due Tracking, English & Hindi",
  "softwareVersion": "<?= htmlspecialchars($apkV) ?>",
  "url": "<?= htmlspecialchars($cfg['lp_page_url'] ?? 'https://binest.app') ?>"
}
</script>

<script>
window.addEventListener('scroll',()=>{
  document.getElementById('nav').classList.toggle('scrolled',window.scrollY>20);
  document.getElementById('scrollTop').classList.toggle('visible',window.scrollY>500);
},{passive:true});

const io=new IntersectionObserver(entries=>{
  entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('on');io.unobserve(e.target);}});
},{threshold:0.1});
document.querySelectorAll('.rv,.rv-l,.rv-r').forEach(el=>io.observe(el));

/* Mobile hamburger menu */
const hamburger=document.getElementById('hamburger');
const mobileMenu=document.getElementById('mobileMenu');
if(hamburger&&mobileMenu){
  hamburger.addEventListener('click',()=>{
    hamburger.classList.toggle('active');
    mobileMenu.classList.toggle('open');
  });
  mobileMenu.querySelectorAll('a').forEach(a=>{
    a.addEventListener('click',()=>{
      hamburger.classList.remove('active');
      mobileMenu.classList.remove('open');
    });
  });
}

/* Scroll to top */
document.getElementById('scrollTop').addEventListener('click',()=>{
  window.scrollTo({top:0,behavior:'smooth'});
});

document.querySelectorAll('.faq-q').forEach(btn=>{
  btn.addEventListener('click',()=>{
    const item=btn.closest('.faq-item'),isOpen=item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(i=>i.classList.remove('open'));
    if(!isOpen)item.classList.add('open');
  });
});

let isDragging=false,startX=0,scrollLeft=0;
const track=document.querySelector('.ss-track');
if(track){
  track.addEventListener('mousedown',e=>{isDragging=true;startX=e.pageX-track.offsetLeft;scrollLeft=track.scrollLeft;});
  track.addEventListener('mouseleave',()=>isDragging=false);
  track.addEventListener('mouseup',()=>isDragging=false);
  track.addEventListener('mousemove',e=>{if(!isDragging)return;e.preventDefault();const x=e.pageX-track.offsetLeft;track.scrollLeft=scrollLeft-(x-startX)*1.5;});
}

function validateEmail(v){return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);}
function validatePhone(v){return /^[\+]?[\d\s\-\(\)]{8,}$/.test(v);}

async function sendForm(){
  const name=document.getElementById('fmName').value.trim();
  const contact=document.getElementById('fmContact').value.trim();
  const note=document.getElementById('fmNote').value.trim();
  const honey=document.getElementById('fmWebsite').value.trim();
  const btn=document.getElementById('fmBtn');
  const res=document.getElementById('fmResult');

  document.querySelectorAll('.field-err').forEach(e=>e.classList.remove('show'));
  res.style.display='none';

  let hasErr=false;
  if(!name){document.getElementById('errName').classList.add('show');hasErr=true;}
  if(!contact||(!validateEmail(contact)&&!validatePhone(contact))){document.getElementById('errContact').classList.add('show');hasErr=true;}
  if(honey){showResult('❌ Spam detected.',false);return;}
  if(hasErr)return;

  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Sending...';
  try{
    const fd=new FormData();fd.append('name',name);fd.append('contact',contact);fd.append('message',note);fd.append('type','form');
    const r=await fetch('save_request.php',{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){
      showResult('✅ Message sent! We\'ll reach you soon.',true);
      document.getElementById('fmName').value='';
      document.getElementById('fmContact').value='';
      document.getElementById('fmNote').value='';
    }else showResult('❌ '+d.message,false);
  }catch(e){showResult('❌ Network error. Please try again.',false);}
  btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane"></i> Send Message';
}
function showResult(msg,ok){
  const el=document.getElementById('fmResult');
  el.textContent=msg;
  el.style.cssText=`display:block;padding:12px 16px;border-radius:10px;font-size:13.5px;font-weight:600;text-align:center;margin-bottom:14px;background:${ok?'#ECFDF5':'#FEF2F2'};color:${ok?'#059669':'#DC2626'};border-color:${ok?'#A7F3D0':'#FECACA'}`;
}
</script>

<!-- COOKIE BANNER -->
<div class="cookie-banner" id="cookieBanner">
  <p>We use cookies to improve your experience. By continuing, you agree to our <a href="privacy.php">Privacy Policy</a>.</p>
  <button class="btn-accept" onclick="acceptCookies()">Got it</button>
</div>

<script src="translations.js"></script>
<script>
/* Cookie consent */
function acceptCookies(){
  localStorage.setItem('binest_cookies','accepted');
  document.getElementById('cookieBanner').classList.remove('show');
}
if(localStorage.getItem('binest_cookies')!=='accepted'){
  setTimeout(()=>document.getElementById('cookieBanner').classList.add('show'),1500);
}

/* Language switcher */
let currentLang=localStorage.getItem('binest_lang')||'en';
document.getElementById('langSelect').value=currentLang;
function changeLang(lang){
  if(!window.T||!T[lang])return;
  currentLang=lang;
  localStorage.setItem('binest_lang',lang);
  const t=T[lang];
  /* Update nav */
  const navLinks=document.querySelectorAll('.nav-links a');
  if(navLinks[0])navLinks[0].textContent=t.nav_features||'Features';
  if(navLinks[1])navLinks[1].textContent=t.nav_screenshots||'Screenshots';
  if(navLinks[2])navLinks[2].textContent=t.nav_how||'How it Works';
  if(navLinks[3])navLinks[3].textContent='FAQ';
  /* Update hero */
  const heroH1=document.querySelector('.hero-h1');
  if(heroH1&&t.hero_h1a)heroH1.innerHTML=t.hero_h1a+'<br><em>'+t.hero_h1b+'</em>';
  /* Update mobile menu */
  const mobLinks=document.querySelectorAll('.mobile-menu a');
  if(mobLinks[0])mobLinks[0].textContent=t.nav_features||'Features';
  if(mobLinks[1])mobLinks[1].textContent=t.nav_screenshots||'Screenshots';
  if(mobLinks[2])mobLinks[2].textContent=t.nav_how||'How it Works';
  if(mobLinks[3])mobLinks[3].textContent='FAQ';
  if(mobLinks[4])mobLinks[4].textContent=t.nav_contact||'Contact';
}
/* Apply saved language on load */
if(currentLang!=='en'&&window.T&&T[currentLang])changeLang(currentLang);
</script>
</body>
</html>
