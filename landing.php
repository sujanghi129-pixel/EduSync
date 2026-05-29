<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSync — Student Record System</title>

<!-- Google Fonts: Outfit used as the sole typeface across the entire landing page -->
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<style>

/* ════════════════════════════════════════════════════════════════
   RESET & BASE
   Remove default browser margin/padding and enforce box-sizing
   so padding never bleeds outside an element's declared width.
════════════════════════════════════════════════════════════════ */
*{box-sizing:border-box;margin:0;padding:0;}

/* Smooth anchor scrolling for the "Learn More" / footer nav links */
html{scroll-behavior:smooth;}

body{font-family:'Outfit',sans-serif;background:#f8fafc;color:#1e293b;}

/* Strip underlines and inherit colour by default; individual links
   override these as needed */
a{text-decoration:none;color:inherit;}


/* ════════════════════════════════════════════════════════════════
   TOP NAVIGATION BAR
   Sticky bar that stays at the top of the viewport while the user
   scrolls. Contains logo, nav links, and CTA buttons.
════════════════════════════════════════════════════════════════ */
.topnav{
  background:#ffffff;
  border-bottom:1px solid #e2e8f0;
  padding:0 48px;
  height:68px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  position:sticky;   /* Keeps the bar visible during scroll */
  top:0;
  z-index:100;       /* Sits above all page content */
  box-shadow:0 1px 4px rgba(0,0,0,.06);
}

/* Logo: image + wordmark side by side */
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.nav-logo img{height:36px;width:36px;object-fit:contain;flex-shrink:0;}
.nav-logo-name{font-size:1.05rem;font-weight:700;color:#1e293b;letter-spacing:-.02em;}

/* Centre nav links — hidden on mobile via the media query below */
.nav-links{display:flex;align-items:center;gap:30px;}
.nav-links a{font-size:14px;font-weight:500;color:#64748b;transition:color .15s;}
.nav-links a:hover{color:#3b82f6;}  /* Blue accent on hover */

/* Right-side button group */
.nav-right{display:flex;align-items:center;gap:12px;}

/* Ghost/outline button — used for "Log In" in the nav */
.btn-outline{
  padding:9px 22px;
  border-radius:9px;
  border:1.5px solid #3b82f6;
  color:#3b82f6;
  font-size:14px;font-weight:600;
  cursor:pointer;
  background:transparent;
  transition:all .15s;
  font-family:'Outfit',sans-serif;
  text-decoration:none;
  display:inline-block;
}
.btn-outline:hover{background:#eff6ff;}  /* Faint blue fill on hover */

/* Solid primary button — used for "Get Started" in the nav */
.btn-primary{
  padding:9px 22px;
  border-radius:9px;
  background:#3b82f6;
  color:#fff;
  font-size:14px;font-weight:600;
  cursor:pointer;
  border:none;
  transition:all .15s;
  font-family:'Outfit',sans-serif;
  text-decoration:none;
  display:inline-block;
}
.btn-primary:hover{background:#2563eb;}  /* Darker blue on hover */


/* ════════════════════════════════════════════════════════════════
   HERO SECTION
   Full-width dark gradient banner with headline, subtext,
   CTA buttons, and optional stat strip.
════════════════════════════════════════════════════════════════ */
.hero{
  background:linear-gradient(160deg,#0f172a 0%,#1a3a6e 60%,#0f172a 100%);
  color:#fff;
  padding:110px 48px 90px;
  text-align:center;
}

/* Small pill badge above the headline ("🎓 Student Record System") */
.hero-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(59,130,246,.15);       /* Translucent blue background */
  border:1px solid rgba(59,130,246,.3);
  border-radius:20px;
  padding:7px 18px;
  font-size:13px;font-weight:600;
  color:#93c5fd;
  margin-bottom:28px;
  letter-spacing:.04em;
}

/* Large display headline */
.hero h1{font-size:54px;font-weight:900;line-height:1.1;margin-bottom:22px;letter-spacing:-.02em;}
/* Sky-blue accent on the "EduSync" word inside the headline */
.hero h1 span{color:#38bdf8;}

/* Subtitle paragraph below the headline */
.hero p{font-size:18px;color:#94a3b8;max-width:600px;margin:0 auto 44px;line-height:1.7;}

/* Flex row containing the two hero CTA buttons */
.hero-btns{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;}

/* Primary hero button — larger than the nav variant */
.hero-btn-main{
  padding:15px 36px;border-radius:12px;
  background:#3b82f6;color:#fff;
  font-size:16px;font-weight:700;
  border:none;transition:all .2s;
  font-family:'Outfit',sans-serif;
  text-decoration:none;display:inline-block;
}
.hero-btn-main:hover{background:#2563eb;transform:translateY(-2px);}  /* Slight lift on hover */

/* Secondary/ghost hero button ("Learn More") */
.hero-btn-sec{
  padding:15px 36px;border-radius:12px;
  background:rgba(255,255,255,.1);     /* Semi-transparent white */
  color:#fff;font-size:16px;font-weight:600;
  border:1px solid rgba(255,255,255,.2);
  transition:all .2s;
  font-family:'Outfit',sans-serif;
  cursor:pointer;display:inline-block;
}
.hero-btn-sec:hover{background:rgba(255,255,255,.16);}

/* Stat strip at the bottom of the hero, separated by a faint top border */
.hero-stats{
  display:flex;justify-content:center;gap:56px;
  margin-top:72px;flex-wrap:wrap;
  border-top:1px solid rgba(255,255,255,.08);
  padding-top:56px;
}
.hero-stat{text-align:center;}
.hero-stat-num{font-size:30px;font-weight:800;color:#fff;}
.hero-stat-lbl{font-size:12px;color:#64748b;margin-top:4px;font-weight:500;}


/* ════════════════════════════════════════════════════════════════
   GENERIC CONTENT SECTION
   Reusable wrapper with generous vertical padding, used for the
   Features block and any future full-width sections.
════════════════════════════════════════════════════════════════ */
.section{padding:90px 48px;}

/* Centred section header: tag → title → subtitle */
.section-center{text-align:center;margin-bottom:60px;}
.section-tag{font-size:12px;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:.12em;margin-bottom:12px;}
.section-title{font-size:36px;font-weight:800;color:#1e293b;letter-spacing:-.02em;margin-bottom:14px;}
.section-sub{font-size:15px;color:#64748b;max-width:540px;margin:0 auto;line-height:1.8;}


/* ════════════════════════════════════════════════════════════════
   FEATURE CARDS GRID
   3-column grid of cards; each card highlights one system feature.
   Collapses to a single column on mobile.
════════════════════════════════════════════════════════════════ */
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;max-width:1040px;margin:0 auto;}
.feat-card{
  background:#fff;
  border:1px solid #e2e8f0;
  border-radius:16px;
  padding:30px;
  transition:all .2s;
}
/* Border accent + lift + shadow on hover */
.feat-card:hover{border-color:#3b82f6;transform:translateY(-4px);box-shadow:0 16px 36px rgba(59,130,246,.1);}

/* Coloured square icon container above the card title */
.feat-icon{
  width:52px;height:52px;border-radius:13px;
  display:flex;align-items:center;justify-content:center;
  font-size:24px;margin-bottom:20px;
}
.feat-title{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:9px;}
.feat-desc{font-size:13px;color:#64748b;line-height:1.75;}


/* ════════════════════════════════════════════════════════════════
   ROLES SECTION
   Light-blue tinted background section showing the three user
   roles and their associated permissions.
════════════════════════════════════════════════════════════════ */
.roles-section{background:#f0f9ff;padding:90px 48px;}
.roles-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;max-width:960px;margin:0 auto;}
.role-card{
  background:#fff;border:1px solid #e2e8f0;
  border-radius:16px;padding:30px;text-align:center;
  transition:all .2s;
}
.role-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.07);}

/* Circular avatar showing initials or an icon, colour-coded per role */
.role-avatar{
  width:68px;height:68px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:22px;font-weight:800;
  margin:0 auto 18px;
}
.role-title{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:12px;}

/* Permission list: no bullets — green checkmark injected via ::before */
.role-perms{list-style:none;font-size:13px;color:#64748b;line-height:2.1;text-align:left;}
.role-perms li::before{content:"✓  ";color:#10b981;font-weight:700;}


/* ════════════════════════════════════════════════════════════════
   TECH STACK / COMPONENTS GRID
   5-column grid of coloured cards showing the technologies used.
════════════════════════════════════════════════════════════════ */
.comp-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;max-width:1040px;margin:0 auto;}
.comp-card{
  border-radius:14px;padding:26px 16px;text-align:center;
  border:1px solid;             /* Border colour set inline per card */
  transition:transform .2s;
}
.comp-card:hover{transform:translateY(-4px);}
.comp-icon{font-size:32px;margin-bottom:12px;}
.comp-name{font-size:13px;font-weight:700;margin-bottom:4px;}
.comp-dev{font-size:12px;opacity:.7;}  /* Subtitle/developer credit below the name */


/* ════════════════════════════════════════════════════════════════
   CALL-TO-ACTION SECTION
   Dark gradient banner above the footer nudging users to sign in.
════════════════════════════════════════════════════════════════ */
.cta-section{background:linear-gradient(135deg,#1e3a5f,#0f172a);padding:90px 48px;text-align:center;color:#fff;}
.cta-section h2{font-size:38px;font-weight:800;margin-bottom:16px;letter-spacing:-.02em;}
.cta-section p{font-size:16px;color:#94a3b8;margin-bottom:38px;max-width:520px;margin-left:auto;margin-right:auto;line-height:1.7;}

/* Large sign-in button inside the CTA section */
.cta-btn{
  display:inline-block;
  padding:17px 44px;border-radius:12px;
  background:#3b82f6;color:#fff;
  font-size:17px;font-weight:700;
  transition:all .2s;
  font-family:'Outfit',sans-serif;
  text-decoration:none;
}
.cta-btn:hover{background:#2563eb;transform:translateY(-2px);}


/* ════════════════════════════════════════════════════════════════
   FOOTER
   Dark background with a 4-column grid: brand blurb + 3 link
   columns. Bottom bar has copyright and tech-stack badges.
════════════════════════════════════════════════════════════════ */
.footer{background:#0f172a;color:#94a3b8;padding:64px 48px 32px;}

/* 4-column layout: brand (2fr) + three link columns (1fr each) */
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:44px;margin-bottom:52px;}

/* Logo row inside the footer brand column */
.footer-logo{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.footer-logo img{height:36px;width:36px;object-fit:contain;flex-shrink:0;filter:brightness(1.2);}  /* Slightly brighter on dark bg */
.footer-logo-name{font-size:1rem;font-weight:700;color:#fff;letter-spacing:-.02em;}
.footer-brand p{font-size:13px;line-height:1.8;max-width:270px;color:#64748b;}

/* Generic link column: small all-caps heading + stacked links */
.footer-col h4{font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.12em;margin-bottom:18px;}
.footer-col a{display:block;font-size:13px;color:#64748b;margin-bottom:11px;transition:color .15s;}
.footer-col a:hover{color:#3b82f6;}

/* Bottom bar: copyright left, badges right */
.footer-bottom{border-top:1px solid #1e293b;padding-top:26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;}
.footer-bottom p{font-size:12px;}

/* Pill badges showing the tech stack (PHP, MySQL, etc.) */
.footer-badges{display:flex;gap:8px;flex-wrap:wrap;}
.footer-badge{font-size:11px;padding:4px 12px;border-radius:20px;background:#1e293b;color:#64748b;border:1px solid #334155;}


/* ════════════════════════════════════════════════════════════════
   RESPONSIVE BREAKPOINTS
   Simplify layout for tablets and phones:
     ≤ 768px — single-column grids, hidden nav links, smaller hero text
════════════════════════════════════════════════════════════════ */
@media(max-width:768px){
  .topnav{padding:0 20px;}
  .nav-links{display:none;}              /* Hide centre nav links on mobile */
  .hero{padding:70px 20px 60px;}
  .hero h1{font-size:36px;}              /* Scale down display headline */
  .section{padding:60px 20px;}
  .roles-section,.cta-section{padding:60px 20px;}
  .features-grid,.roles-grid{grid-template-columns:1fr;}  /* Stack to single column */
  .comp-grid{grid-template-columns:repeat(2,1fr);}        /* 2-col on tablet */
  .footer-top{grid-template-columns:1fr;}  /* Stack footer columns */
  .footer{padding:44px 20px 24px;}
}

/* ════════════════════════════════════════════════════════════════
   PRIVACY POLICY MODAL
   Full-screen dark overlay with a centred scrollable content box.
   Triggered by clicking the "Privacy Policy" badge in the footer.
════════════════════════════════════════════════════════════════ */

/* Semi-transparent overlay covers the entire viewport */
.privacy-overlay{
  display:none;                        /* Hidden by default; JS sets display:flex */
  position:fixed;
  inset:0;                             /* top/right/bottom/left all 0 */
  background:rgba(0,0,0,.7);
  backdrop-filter:blur(4px);
  z-index:999;
  align-items:center;
  justify-content:center;
  padding:20px;
}
.privacy-overlay.open{ display:flex; }

/* The white modal card */
.privacy-modal{
  background:#fff;
  border-radius:20px;
  width:100%;
  max-width:680px;
  max-height:88vh;
  display:flex;
  flex-direction:column;
  box-shadow:0 32px 80px rgba(0,0,0,.35);
  overflow:hidden;
}

/* Sticky header bar inside the modal */
.privacy-modal-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:24px 30px 20px;
  border-bottom:1px solid #e2e8f0;
  flex-shrink:0;
}
.privacy-modal-header h2{
  font-size:18px;
  font-weight:800;
  color:#1e293b;
  letter-spacing:-.02em;
}
.privacy-modal-header span{
  font-size:11px;
  font-weight:600;
  color:#64748b;
  background:#f1f5f9;
  padding:4px 10px;
  border-radius:20px;
}

/* Close button — top right of the header */
.privacy-close{
  background:none;
  border:none;
  font-size:22px;
  color:#94a3b8;
  cursor:pointer;
  line-height:1;
  padding:4px;
  border-radius:6px;
  transition:color .15s, background .15s;
}
.privacy-close:hover{ color:#1e293b; background:#f1f5f9; }

/* Scrollable body */
.privacy-modal-body{
  padding:28px 30px;
  overflow-y:auto;
  flex:1;
}

/* Section headings inside the policy */
.privacy-modal-body h3{
  font-size:14px;
  font-weight:700;
  color:#1e293b;
  margin:22px 0 8px;
  display:flex;
  align-items:center;
  gap:8px;
}
.privacy-modal-body h3:first-child{ margin-top:0; }

/* Body paragraphs */
.privacy-modal-body p{
  font-size:13px;
  color:#64748b;
  line-height:1.8;
  margin-bottom:6px;
}

/* Inline pill tags (e.g. data items listed) */
.privacy-tag{
  display:inline-block;
  font-size:11px;
  font-weight:600;
  padding:3px 10px;
  border-radius:20px;
  background:#eff6ff;
  color:#3b82f6;
  border:1px solid #bfdbfe;
  margin:2px 2px 2px 0;
}

/* Divider line between sections */
.privacy-divider{
  border:none;
  border-top:1px solid #f1f5f9;
  margin:20px 0;
}

/* Sticky footer bar with last-updated info */
.privacy-modal-footer{
  padding:16px 30px;
  border-top:1px solid #e2e8f0;
  background:#f8fafc;
  font-size:12px;
  color:#94a3b8;
  flex-shrink:0;
  display:flex;
  align-items:center;
  justify-content:space-between;
}
.privacy-modal-footer a{
  color:#3b82f6;
  font-weight:600;
  text-decoration:none;
}
.privacy-modal-footer a:hover{ text-decoration:underline; }

/* Make the footer badge look like a clickable link */
.footer-badge-link{
  cursor:pointer;
  transition:background .15s, color .15s;
}
.footer-badge-link:hover{
  background:#3b82f6;
  color:#fff;
  border-color:#3b82f6;
}
</style>
</head>
<body>

<!-- ════════════════════════════════════════════════════════════
     TOP NAVIGATION
     Sticky bar: logo on the left, anchor links in the centre,
     Log In + Get Started buttons on the right.
════════════════════════════════════════════════════════════════ -->
<nav class="topnav">

  <!-- Logo: links back to the landing page itself -->
  <a href="landing.php" class="nav-logo">
    <img src="shared/logo.png" alt="EduSync">
    <span class="nav-logo-name">EduSync</span>
  </a>

  <!-- Anchor links to page sections (hidden on mobile) -->
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#about">About</a>
  </div>

  <!-- Both buttons link to the login page -->
  <div class="nav-right">
    <a href="index.php" class="btn-outline">Log In</a>
    <a href="index.php" class="btn-primary">Get Started</a>
  </div>
</nav>


<!-- ════════════════════════════════════════════════════════════
     HERO SECTION
     Dark gradient banner with headline, subtext, and two CTAs.
════════════════════════════════════════════════════════════════ -->
<section class="hero">

  <!-- Small decorative badge above the headline -->
  <div class="hero-badge">🎓 Student Record System</div>

  <!-- Main headline — "EduSync" highlighted in sky blue via <span> -->
  <h1>Manage your school<br>with <span>EduSync</span></h1>

  <!-- Supporting subtext describing the system -->
  <p>A secure, role-based web application for managing student records, attendance, grades, classes and staff — all in one place.</p>

  <!-- CTA button row -->
  <div class="hero-btns">
    <!-- Primary CTA: takes the user straight to the login page -->
    <a href="index.php" class="hero-btn-main">Log In to EduSync &rarr;</a>

    <!-- Secondary CTA: smooth-scrolls to the #features section via inline JS -->
    <button class="hero-btn-sec" onclick="document.getElementById('features').scrollIntoView({behavior:'smooth'})">Learn More</button>
  </div>

</section>


<!-- ════════════════════════════════════════════════════════════
     FEATURES SECTION
     id="features" is the scroll target for the "Learn More" button.
     Light grey background to visually separate it from the hero.
════════════════════════════════════════════════════════════════ -->
<section class="section" id="features" style="background:#f8fafc;">

  <!-- Section header: tag line, title, subtitle -->
  <div class="section-center">
    <div class="section-tag">Features</div>
    <div class="section-title">Everything your school needs</div>
    <div class="section-sub">Built for administrators, teachers and headteachers — with role-based access so everyone sees only what they need.</div>
  </div>

  <!-- 3-column feature card grid -->
  <div class="features-grid">

    <!-- Each card: coloured icon square, bold title, short description -->

    <!-- Feature 1: Role-Based Access -->
    <div class="feat-card">
      <div class="feat-icon" style="background:#eff6ff;">🔐</div>
      <div class="feat-title">Role-Based Access</div>
      <div class="feat-desc">Three roles — Administrator, Teacher and Headteacher — each with their own level of access and permissions.</div>
    </div>

    <!-- Feature 2: Student Management -->
    <div class="feat-card">
      <div class="feat-icon" style="background:#ecfdf5;">👦</div>
      <div class="feat-title">Student Management</div>
      <div class="feat-desc">Add, search, edit and manage student profiles. Assign students to grades and classes with full validation.</div>
    </div>

    <!-- Feature 3: Attendance Tracking -->
    <div class="feat-card">
      <div class="feat-icon" style="background:#fef3c7;">✅</div>
      <div class="feat-title">Attendance Tracking</div>
      <div class="feat-desc">Mark daily attendance as Present, Late or Absent. Filter records by class, student or date.</div>
    </div>

    <!-- Feature 4: Class & Grade Management -->
    <div class="feat-card">
      <div class="feat-icon" style="background:#f5f3ff;">🏫</div>
      <div class="feat-title">Class &amp; Grade Management</div>
      <div class="feat-desc">Manage grade levels and class groups. Assign teachers to classes and link classes to grades.</div>
    </div>

    <!-- Feature 5: Staff Accounts -->
    <div class="feat-card">
      <div class="feat-icon" style="background:#fce7f3;">👤</div>
      <div class="feat-title">Staff Accounts</div>
      <div class="feat-desc">Manage staff accounts with bcrypt password hashing. GDPR-compliant deactivation on leaving.</div>
    </div>

    <!-- Feature 6: Live Dashboard -->
    <div class="feat-card">
      <div class="feat-icon" style="background:#ecfeff;">📊</div>
      <div class="feat-title">Live Dashboard</div>
      <div class="feat-desc">Real-time stats showing active staff, students, classes, grades and today's attendance at a glance.</div>
    </div>

  </div><!-- /.features-grid -->
</section>


<!-- ════════════════════════════════════════════════════════════
     FOOTER
     id="about" is the scroll target for the "About" nav link.
     4-column grid: brand blurb + System / Access / Project links.
     Bottom bar has copyright and tech-stack badge pills.
════════════════════════════════════════════════════════════════ -->
<footer class="footer" id="about">
  <div class="footer-top">

    <!-- Brand column: logo + project description -->
    <div class="footer-brand">
      <div class="footer-logo">
        <img src="shared/logo.png" alt="EduSync">
        <span class="footer-logo-name">EduSync</span>
      </div>
      <!-- Brief project context for markers and external visitors -->
      <p>A web-based student record system developed as part of CTEC2713 Agile Development at Niels Brock Copenhagen Business College. Built by a team of five developers.</p>
    </div>

    <!-- System link column: quick access to main app sections -->
    <div class="footer-col">
      <h4>System</h4>
      <a href="index.php">Sign In</a>
      <a href="index.php">Dashboard</a>
      <a href="index.php">Students</a>
      <a href="index.php">Attendance</a>
      <a href="index.php">Staff</a>
    </div>

    <!-- Access column: role-specific entry points -->
    <div class="footer-col">
      <h4>Access</h4>
      <a href="index.php">Sign In</a>
      <a href="#roles">Administrator</a>
      <a href="#roles">Teacher</a>
      <a href="#roles">Headteacher</a>
    </div>

    <!-- Project column: module, methodology and team info -->
    <div class="footer-col">
      <h4>Project</h4>
      <a href="#about">CTEC2713</a>
      <a href="#about">Agile Development</a>
      <a href="#about">Student Record System</a>
      <a href="#components">Our Team</a>
    </div>

  </div><!-- /.footer-top -->

  <!-- Bottom bar: copyright left, technology badge pills right -->
  <div class="footer-bottom">
    <p>&copy; 2026 EduSync &mdash; Student Record System.</p>

    <!-- Tech stack pills — purely decorative, no links -->
    <div class="footer-badges">
      <span class="footer-badge footer-badge-link" onclick="document.getElementById('privacyModal').classList.add('open')">🔒 Privacy Policy</span>
      <span class="footer-badge">PHP</span>
      <span class="footer-badge">MySQL</span>
      <span class="footer-badge">HTML/CSS/JS</span>
      <span class="footer-badge">GDPR</span>
      <span class="footer-badge">Agile</span>
    </div>
  </div><!-- /.footer-bottom -->

</footer>


<!-- ════════════════════════════════════════════════════════════
     PRIVACY POLICY MODAL
     Hidden by default. Opens when the "Privacy Policy" badge is
     clicked. Closes via the × button, the overlay, or Escape key.
════════════════════════════════════════════════════════════════ -->
<div class="privacy-overlay" id="privacyModal" onclick="if(event.target===this)closePrivacy()">
  <div class="privacy-modal" role="dialog" aria-modal="true" aria-labelledby="privacyTitle">

    <!-- Modal header -->
    <div class="privacy-modal-header">
      <div>
        <h2 id="privacyTitle">🔒 Privacy Policy</h2>
      </div>
      <span>Last updated: May 2026</span>
      <button class="privacy-close" onclick="closePrivacy()" aria-label="Close privacy policy">&times;</button>
    </div>

    <!-- Scrollable policy content -->
    <div class="privacy-modal-body">

      <h3>📋 Who we are</h3>
      <p>EduSync is a web-based student record system developed as part of CTEC2713 Agile Development at Niels Brock Copenhagen Business College. It is operated exclusively by authorised school staff.</p>

      <hr class="privacy-divider">

      <h3>📦 What personal data we collect</h3>
      <p>EduSync collects and stores the minimum personal data necessary to operate the system. This includes:</p>
      <p>
        <span class="privacy-tag">Full name</span>
        <span class="privacy-tag">Username</span>
        <span class="privacy-tag">Role (Administrator / Teacher / Headteacher)</span>
        <span class="privacy-tag">Account status (active / inactive)</span>
        <span class="privacy-tag">Encrypted password</span>
        <span class="privacy-tag">Account creation date</span>
      </p>
      <p>Passwords are never stored in plain text — they are protected using bcrypt one-way hashing. No email addresses, phone numbers, or other personal identifiers are collected.</p>

      <hr class="privacy-divider">

      <h3>🎯 Why we collect it</h3>
      <p>Personal data is collected solely for the purpose of <strong>school staff authentication and access management</strong>. Each piece of data serves a specific operational function and is not used for any other purpose.</p>

      <hr class="privacy-divider">

      <h3>👁️ Who can see your data</h3>
      <p>Access to personal data is strictly role-gated. Only users with the <strong>Administrator</strong> role can view, add, edit, deactivate, or delete staff records. Teachers and Headteachers cannot access staff management pages.</p>

      <hr class="privacy-divider">

      <h3>🔒 How we protect your data</h3>
      <p>EduSync applies multiple technical security measures in line with GDPR Article 25 (data protection by design):</p>
      <p>
        <span class="privacy-tag">Bcrypt password hashing</span>
        <span class="privacy-tag">SQL injection protection (PDO)</span>
        <span class="privacy-tag">XSS prevention</span>
        <span class="privacy-tag">Role-based access control</span>
        <span class="privacy-tag">Session hardening</span>
        <span class="privacy-tag">Brute-force lockout</span>
      </p>

      <hr class="privacy-divider">

      <h3>⏱️ How long we keep your data</h3>
      <p>Staff records are retained for as long as the account is operationally required. When a staff member leaves, their account is <strong>deactivated</strong> rather than deleted, preserving the audit trail. Permanent deletion is available to Administrators but requires explicit confirmation.</p>

      <hr class="privacy-divider">

      <h3>🌍 Your rights under GDPR</h3>
      <p>As a data subject under the General Data Protection Regulation (EU) 2016/679, you have the following rights:</p>
      <p>
        <span class="privacy-tag">Right to access your data</span>
        <span class="privacy-tag">Right to correct inaccurate data</span>
        <span class="privacy-tag">Right to erasure</span>
        <span class="privacy-tag">Right to object to processing</span>
        <span class="privacy-tag">Right to data portability</span>
      </p>
      <p>To exercise any of these rights, contact your school Administrator directly.</p>

      <hr class="privacy-divider">

      <h3>🚫 What we do not do</h3>
      <p>EduSync does <strong>not</strong> sell, share, or transfer personal data to any third parties. No data is used for advertising, profiling, or automated decision-making.</p>

    </div><!-- /.privacy-modal-body -->

    <!-- Sticky footer bar -->
    <div class="privacy-modal-footer">
      <span>📄 In compliance with GDPR (EU) 2016/679</span>
      <span>Questions? Contact your school Administrator.</span>
    </div>

  </div><!-- /.privacy-modal -->
</div><!-- /.privacy-overlay -->


<script>
  /* Close the privacy modal */
  function closePrivacy() {
    document.getElementById('privacyModal').classList.remove('open');
  }

  /* Allow Escape key to close the modal */
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closePrivacy();
  });
</script>

</body>
</html>