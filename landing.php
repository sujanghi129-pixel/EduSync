<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduSync — Student Record System</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Outfit',sans-serif;background:#f8fafc;color:#1e293b;}
a{text-decoration:none;color:inherit;}

.topnav{
  background:#ffffff;border-bottom:1px solid #e2e8f0;
  padding:0 48px;height:68px;display:flex;align-items:center;
  justify-content:space-between;position:sticky;top:0;z-index:100;
  box-shadow:0 1px 4px rgba(0,0,0,.06);
}
.nav-logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.nav-logo img{height:36px;width:36px;object-fit:contain;flex-shrink:0;}
.nav-logo-name{font-size:1.05rem;font-weight:700;color:#1e293b;letter-spacing:-.02em;}
.nav-links{display:flex;align-items:center;gap:30px;}
.nav-links a{font-size:14px;font-weight:500;color:#64748b;transition:color .15s;}
.nav-links a:hover{color:#3b82f6;}
.nav-right{display:flex;align-items:center;gap:12px;}
.btn-outline{
  padding:9px 22px;border-radius:9px;border:1.5px solid #3b82f6;
  color:#3b82f6;font-size:14px;font-weight:600;cursor:pointer;
  background:transparent;transition:all .15s;font-family:'Outfit',sans-serif;
  text-decoration:none;display:inline-block;
}
.btn-outline:hover{background:#eff6ff;}
.btn-primary{
  padding:9px 22px;border-radius:9px;background:#3b82f6;
  color:#fff;font-size:14px;font-weight:600;cursor:pointer;
  border:none;transition:all .15s;font-family:'Outfit',sans-serif;
  text-decoration:none;display:inline-block;
}
.btn-primary:hover{background:#2563eb;}

.hero{
  background:linear-gradient(160deg,#0f172a 0%,#1a3a6e 60%,#0f172a 100%);
  color:#fff;padding:110px 48px 90px;text-align:center;
}
.hero-badge{
  display:inline-flex;align-items:center;gap:8px;
  background:rgba(59,130,246,.15);border:1px solid rgba(59,130,246,.3);
  border-radius:20px;padding:7px 18px;font-size:13px;font-weight:600;
  color:#93c5fd;margin-bottom:28px;letter-spacing:.04em;
}
.hero h1{font-size:54px;font-weight:900;line-height:1.1;margin-bottom:22px;letter-spacing:-.02em;}
.hero h1 span{color:#38bdf8;}
.hero p{font-size:18px;color:#94a3b8;max-width:600px;margin:0 auto 44px;line-height:1.7;}
.hero-btns{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;}
.hero-btn-main{
  padding:15px 36px;border-radius:12px;background:#3b82f6;
  color:#fff;font-size:16px;font-weight:700;
  border:none;transition:all .2s;font-family:'Outfit',sans-serif;
  text-decoration:none;display:inline-block;
}
.hero-btn-main:hover{background:#2563eb;transform:translateY(-2px);}
.hero-btn-sec{
  padding:15px 36px;border-radius:12px;background:rgba(255,255,255,.1);
  color:#fff;font-size:16px;font-weight:600;
  border:1px solid rgba(255,255,255,.2);transition:all .2s;
  font-family:'Outfit',sans-serif;cursor:pointer;display:inline-block;
}
.hero-btn-sec:hover{background:rgba(255,255,255,.16);}
.hero-stats{
  display:flex;justify-content:center;gap:56px;margin-top:72px;
  flex-wrap:wrap;border-top:1px solid rgba(255,255,255,.08);padding-top:56px;
}
.hero-stat{text-align:center;}
.hero-stat-num{font-size:30px;font-weight:800;color:#fff;}
.hero-stat-lbl{font-size:12px;color:#64748b;margin-top:4px;font-weight:500;}

.section{padding:90px 48px;}
.section-center{text-align:center;margin-bottom:60px;}
.section-tag{font-size:12px;font-weight:700;color:#3b82f6;text-transform:uppercase;letter-spacing:.12em;margin-bottom:12px;}
.section-title{font-size:36px;font-weight:800;color:#1e293b;letter-spacing:-.02em;margin-bottom:14px;}
.section-sub{font-size:15px;color:#64748b;max-width:540px;margin:0 auto;line-height:1.8;}

.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;max-width:1040px;margin:0 auto;}
.feat-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:30px;transition:all .2s;}
.feat-card:hover{border-color:#3b82f6;transform:translateY(-4px);box-shadow:0 16px 36px rgba(59,130,246,.1);}
.feat-icon{width:52px;height:52px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:20px;}
.feat-title{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:9px;}
.feat-desc{font-size:13px;color:#64748b;line-height:1.75;}

.roles-section{background:#f0f9ff;padding:90px 48px;}
.roles-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;max-width:960px;margin:0 auto;}
.role-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:30px;text-align:center;transition:all .2s;}
.role-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.07);}
.role-avatar{width:68px;height:68px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:800;margin:0 auto 18px;}
.role-title{font-size:16px;font-weight:700;color:#1e293b;margin-bottom:12px;}
.role-perms{list-style:none;font-size:13px;color:#64748b;line-height:2.1;text-align:left;}
.role-perms li::before{content:"✓  ";color:#10b981;font-weight:700;}

.comp-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;max-width:1040px;margin:0 auto;}
.comp-card{border-radius:14px;padding:26px 16px;text-align:center;border:1px solid;transition:transform .2s;}
.comp-card:hover{transform:translateY(-4px);}
.comp-icon{font-size:32px;margin-bottom:12px;}
.comp-name{font-size:13px;font-weight:700;margin-bottom:4px;}
.comp-dev{font-size:12px;opacity:.7;}

.cta-section{background:linear-gradient(135deg,#1e3a5f,#0f172a);padding:90px 48px;text-align:center;color:#fff;}
.cta-section h2{font-size:38px;font-weight:800;margin-bottom:16px;letter-spacing:-.02em;}
.cta-section p{font-size:16px;color:#94a3b8;margin-bottom:38px;max-width:520px;margin-left:auto;margin-right:auto;line-height:1.7;}
.cta-btn{
  display:inline-block;padding:17px 44px;border-radius:12px;
  background:#3b82f6;color:#fff;font-size:17px;font-weight:700;
  transition:all .2s;font-family:'Outfit',sans-serif;text-decoration:none;
}
.cta-btn:hover{background:#2563eb;transform:translateY(-2px);}

.footer{background:#0f172a;color:#94a3b8;padding:64px 48px 32px;}
.footer-top{display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:44px;margin-bottom:52px;}
.footer-logo{display:flex;align-items:center;gap:10px;margin-bottom:14px;}
.footer-logo img{height:36px;width:36px;object-fit:contain;flex-shrink:0;filter:brightness(1.2);}
.footer-logo-name{font-size:1rem;font-weight:700;color:#fff;letter-spacing:-.02em;}
.footer-brand p{font-size:13px;line-height:1.8;max-width:270px;color:#64748b;}
.footer-col h4{font-size:11px;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.12em;margin-bottom:18px;}
.footer-col a{display:block;font-size:13px;color:#64748b;margin-bottom:11px;transition:color .15s;}
.footer-col a:hover{color:#3b82f6;}
.footer-bottom{border-top:1px solid #1e293b;padding-top:26px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;}
.footer-bottom p{font-size:12px;}
.footer-badges{display:flex;gap:8px;flex-wrap:wrap;}
.footer-badge{font-size:11px;padding:4px 12px;border-radius:20px;background:#1e293b;color:#64748b;border:1px solid #334155;}

@media(max-width:768px){
  .topnav{padding:0 20px;}
  .nav-links{display:none;}
  .hero{padding:70px 20px 60px;}
  .hero h1{font-size:36px;}
  .section{padding:60px 20px;}
  .roles-section,.cta-section{padding:60px 20px;}
  .features-grid,.roles-grid{grid-template-columns:1fr;}
  .comp-grid{grid-template-columns:repeat(2,1fr);}
  .footer-top{grid-template-columns:1fr;}
  .footer{padding:44px 20px 24px;}
}
</style>
</head>
<body>

<!-- NAVIGATION -->
<nav class="topnav">
  <a href="landing.php" class="nav-logo">
    <img src="shared/logo.png" alt="EduSync">
    <span class="nav-logo-name">EduSync</span>
  </a>
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#about">About</a>
  </div>
  <div class="nav-right">
    <a href="index.php" class="btn-outline">Log In</a>
    <a href="index.php" class="btn-primary">Get Started</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-badge">🎓 Student Record System</div>
  <h1>Manage your school<br>with <span>EduSync</span></h1>
  <p>A secure, role-based web application for managing student records, attendance, grades, classes and staff — all in one place.</p>
  <div class="hero-btns">
    <a href="index.php" class="hero-btn-main">Log In to EduSync &rarr;</a>
    <button class="hero-btn-sec" onclick="document.getElementById('features').scrollIntoView({behavior:'smooth'})">Learn More</button>
  </div>
 
</section>

<!-- FEATURES -->
<section class="section" id="features" style="background:#f8fafc;">
  <div class="section-center">
    <div class="section-tag">Features</div>
    <div class="section-title">Everything your school needs</div>
    <div class="section-sub">Built for administrators, teachers and headteachers — with role-based access so everyone sees only what they need.</div>
  </div>
  <div class="features-grid">
    <div class="feat-card"><div class="feat-icon" style="background:#eff6ff;">🔐</div><div class="feat-title">Role-Based Access</div><div class="feat-desc">Three roles — Administrator, Teacher and Headteacher — each with their own level of access and permissions.</div></div>
    <div class="feat-card"><div class="feat-icon" style="background:#ecfdf5;">👦</div><div class="feat-title">Student Management</div><div class="feat-desc">Add, search, edit and manage student profiles. Assign students to grades and classes with full validation.</div></div>
    <div class="feat-card"><div class="feat-icon" style="background:#fef3c7;">✅</div><div class="feat-title">Attendance Tracking</div><div class="feat-desc">Mark daily attendance as Present, Late or Absent. Filter records by class, student or date.</div></div>
    <div class="feat-card"><div class="feat-icon" style="background:#f5f3ff;">🏫</div><div class="feat-title">Class &amp; Grade Management</div><div class="feat-desc">Manage grade levels and class groups. Assign teachers to classes and link classes to grades.</div></div>
    <div class="feat-card"><div class="feat-icon" style="background:#fce7f3;">👤</div><div class="feat-title">Staff Accounts</div><div class="feat-desc">Manage staff accounts with bcrypt password hashing. GDPR-compliant deactivation on leaving.</div></div>
    <div class="feat-card"><div class="feat-icon" style="background:#ecfeff;">📊</div><div class="feat-title">Live Dashboard</div><div class="feat-desc">Real-time stats showing active staff, students, classes, grades and today's attendance at a glance.</div></div>
  </div>
</section>

<!-- FOOTER -->
<footer class="footer" id="about">
  <div class="footer-top">
    <div class="footer-brand">
      <div class="footer-logo">
        <img src="shared/logo.png" alt="EduSync">
        <span class="footer-logo-name">EduSync</span>
      </div>
      <p>A web-based student record system developed as part of CTEC2713 Agile Development at Niels Brock Copenhagen Business College. Built by a team of five developers.</p>
    </div>
    <div class="footer-col">
      <h4>System</h4>
      <a href="index.php">Sign In</a>
      <a href="index.php">Dashboard</a>
      <a href="index.php">Students</a>
      <a href="index.php">Attendance</a>
      <a href="index.php">Staff</a>
    </div>
    <div class="footer-col">
      <h4>Access</h4>
      <a href="index.php">Sign In</a>
      <a href="#roles">Administrator</a>
      <a href="#roles">Teacher</a>
      <a href="#roles">Headteacher</a>
    </div>
    <div class="footer-col">
      <h4>Project</h4>
      <a href="#about">CTEC2713</a>
      <a href="#about">Agile Development</a>
      <a href="#about">Student Record System</a>
      <a href="#components">Our Team</a>
    </div>
  </div>
  <div class="footer-bottom">
    <p>&copy; 2026 EduSync &mdash; Student Record System.</p>
    <div class="footer-badges">
      <span class="footer-badge">PHP</span>
      <span class="footer-badge">MySQL</span>
      <span class="footer-badge">HTML/CSS/JS</span>
      <span class="footer-badge">GDPR</span>
      <span class="footer-badge">Agile</span>
    </div>
  </div>
</footer>

</body>
</html>