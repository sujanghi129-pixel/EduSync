/**
 * shared/auth.js
 *
 * Client-side authentication and UI helper for the EduSync system.
 * Builds the top nav and sidebar based on the logged-in user's role.
 *
 * Role-based sidebar:
 *  - Administrator → full access to all components
 *  - Teacher / Headteacher → Dashboard + My Class + Attendance only
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

(function () {

  // ── USER DATA ─────────────────────────────────────────────
  /**
   * Read the logged-in user from the <meta name="edu-user"> tag.
   *
   * @type {Object}
   */
  const metaUser = document.querySelector('meta[name="edu-user"]');
  const user     = metaUser ? JSON.parse(metaUser.content) : {};
  const role     = user.role || '';
  const isAdmin  = role === 'Administrator';

  // ── FORCE DARK THEME ──────────────────────────────────────
  document.documentElement.setAttribute('data-theme', 'dark');

  // ── BASE PATH DETECTION ───────────────────────────────────
  /**
   * Detects the root path of the EduSync project dynamically.
   * Works regardless of installation folder name or page depth.
   *
   * Strategy: walk up from the current script's location.
   * shared/auth.js is always one level inside the root, so
   * the root is always one folder up from this script.
   *
   * @type {string} e.g. "/edusync/" or "/edusync-v3/"
   */
  const scriptSrc  = document.currentScript?.src || '';
  const sharedPath = scriptSrc.substring(0, scriptSrc.lastIndexOf('/') + 1);
  const rootPath   = sharedPath.substring(0, sharedPath.lastIndexOf('/', sharedPath.length - 2) + 1);

  /**
   * Builds an absolute URL to a path relative to the site root.
   *
   * @param  {string} rel - e.g. 'attendance/index.php'
   * @return {string}     - e.g. '/edusync/attendance/index.php'
   */
  function url(rel) { return rootPath + rel; }

  // ── ACTIVE PAGE DETECTION ─────────────────────────────────
  const currentPath = window.location.pathname;

  /**
   * Checks whether a given href matches the current page URL.
   *
   * @param  {string} href - Absolute path to check.
   * @return {boolean}
   */
  function isActive(href) {
    return currentPath === href || currentPath.endsWith(href);
  }

  // ── ROLE-BASED SIDEBAR LINKS ──────────────────────────────
  /**
   * Admin navigation links — full system access.
   *
   * @type {Array}
   */
  const adminNavLinks = [
    { href: url('dashboard/index.php'),  icon: '🏠', label: 'Dashboard' },
    { href: url('staff/index.php'),      icon: '👤', label: 'Staff',             section: 'Management' },
    { href: url('students/index.php'),   icon: '🎓', label: 'Students' },
    { href: url('grades/index.php'),     icon: '📚', label: 'Grades' },
    { href: url('classes/index.php'),    icon: '🏫', label: 'Classes' },
    { href: url('attendance/index.php'), icon: '📋', label: 'Mark Attendance',   section: 'Attendance' },
    { href: url('attendance/report.php'),icon: '📊', label: 'Attendance Report' },
  ];

  /**
   * Teacher / Headteacher navigation links — class and attendance only.
   *
   * @type {Array}
   */
  const teacherNavLinks = [
    { href: url('dashboard/index.php'),  icon: '🏠', label: 'Dashboard' },
    { href: url('my_class.php'),         icon: '🎓', label: 'My Class',          section: 'My Class' },
    { href: url('attendance/index.php'), icon: '📋', label: 'Mark Attendance',   section: 'Attendance' },
    { href: url('attendance/report.php'),icon: '📊', label: 'Attendance Report' },
  ];

  /**
   * Active nav links based on current user role.
   *
   * @type {Array}
   */
  const navLinks = isAdmin ? adminNavLinks : teacherNavLinks;

  // ── TOP NAV BUILDER ───────────────────────────────────────
  /**
   * Builds and injects the top navigation bar into #topnav.
   * Shows logo, brand name, avatar and sign out button only.
   *
   * @return {void}
   */
  function buildNav() {
    const nav = document.getElementById('topnav');
    if (!nav) return;

    const initials = user.fullName
      ? user.fullName.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase()
      : 'ES';

    nav.innerHTML = `
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
      <a href="${url('landing.php')}" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
        <img src="${url('shared/logo.png')}" alt="EduSync" style="height:32px;width:32px;object-fit:contain;filter:drop-shadow(0 1px 3px rgba(0,0,0,.5)) brightness(1.08);flex-shrink:0;">
        <span style="font-weight:700;font-size:.95rem;color:var(--text);letter-spacing:-.02em;white-space:nowrap;">EduSync</span>
      </a>
      <div class="nav-spacer"></div>
      <div class="nav-center-links">
        <a href="${url('landing.php')}" class="nav-center-link">Home</a>
        <a href="#" class="nav-center-link" id="nav-about-btn">About</a>
      </div>
      <div class="nav-spacer"></div>
      <div class="nav-right">
        <div class="nav-avatar" title="${esc(user.fullName || 'EduSync')}">${initials}</div>
        <a href="${url('logout.php')}" class="btn-signout">Sign out</a>
      </div>
    `;

    document.getElementById('hamburger')?.addEventListener('click', toggleSidebar);

    document.getElementById('nav-about-btn')?.addEventListener('click', e => {
      e.preventDefault();
      const footer = document.querySelector('.app-footer');
      if (footer) footer.scrollIntoView({ behavior: 'smooth' });
    });
  }

  // ── SIDEBAR BUILDER ───────────────────────────────────────
  /**
   * Builds and injects the sidebar navigation into #sidebar.
   * Shows different links based on the user's role.
   *
   * @return {void}
   */
  function buildSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;

    let html        = '';
    let lastSection = null;

    navLinks.forEach(link => {
      if (link.section && link.section !== lastSection) {
        if (lastSection !== null) html += `<div class="sidebar-divider"></div>`;
        html += `<div class="sidebar-label">${link.section}</div>`;
        lastSection = link.section;
      } else if (!link.section && lastSection === null && html === '') {
        html += `<div class="sidebar-label">Navigation</div>`;
        lastSection = 'Navigation';
      }

      const active = isActive(link.href) ? 'active' : '';
      html += `
        <a href="${link.href}" class="sidebar-link ${active}">
          <span class="si">${link.icon}</span>
          <span>${link.label}</span>
        </a>
      `;
    });

    // Account section — name and role display
    html += `
      <div class="sidebar-divider"></div>
      <div class="sidebar-label">Account</div>
      ${user.fullName ? `
        <div class="sidebar-link" style="cursor:default;">
          <span class="si">👤</span>
          <span style="line-height:1.2;">
            <strong style="display:block;color:var(--text);font-size:.82rem;">${esc(user.fullName)}</strong>
            <span style="font-size:.72rem;color:var(--text-muted);">${esc(role)}</span>
          </span>
        </div>` : ''}
    `;

    sidebar.innerHTML = html;
  }

  // ── MOBILE SIDEBAR TOGGLE ─────────────────────────────────
  /**
   * Toggles the mobile sidebar open/closed.
   *
   * @return {void}
   */
  function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('open');
    document.getElementById('overlay')?.classList.toggle('open');
    document.getElementById('hamburger')?.classList.toggle('open');
  }

  document.getElementById('overlay')?.addEventListener('click', () => {
    document.getElementById('sidebar')?.classList.remove('open');
    document.getElementById('overlay')?.classList.remove('open');
    document.getElementById('hamburger')?.classList.remove('open');
  });

  // ── TOAST NOTIFICATIONS ───────────────────────────────────
  /**
   * Global Toast helper for showing notification messages.
   *
   * @namespace Toast
   */
  window.Toast = {
    /**
     * Shows a toast notification that auto-dismisses after 4 seconds.
     *
     * @param {string} msg  - Message text.
     * @param {string} type - 'success', 'error', or 'info'.
     * @return {void}
     */
    show(msg, type = 'info') {
      let container = document.querySelector('.toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
      }
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      const icons = { success: '✅', error: '⚠️', info: 'ℹ️' };
      toast.innerHTML = `<span>${icons[type] || ''}</span><span>${esc(msg)}</span>`;
      container.appendChild(toast);
      setTimeout(() => toast.remove(), 4000);
    }
  };

  // ── MODAL HELPERS ─────────────────────────────────────────
  /** Opens a modal overlay. @param {string} id @return {void} */
  window.openOverlay  = id => document.getElementById(id)?.classList.add('open');

  /** Closes a modal overlay. @param {string} id @return {void} */
  window.closeOverlay = id => document.getElementById(id)?.classList.remove('open');

  /**
   * Shows or hides an inline form error message.
   *
   * @param {string}      id  - Element ID.
   * @param {string|null} msg - Message or null to hide.
   * @return {void}
   */
  window.showModalErr = (id, msg) => {
    const el = document.getElementById(id);
    if (!el) return;
    if (msg) { el.textContent = msg; el.style.display = 'flex'; }
    else     { el.style.display = 'none'; }
  };

  // ── HTML ESCAPE ───────────────────────────────────────────
  /**
   * Escapes a string for safe HTML insertion.
   *
   * @param  {*}      str - Value to escape.
   * @return {string}
   */
  function esc(str) {
    return String(str ?? '')
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ── FOOTER ────────────────────────────────────────────────
  /**
   * Injects the app footer after the .content area.
   */
  function buildFooter() {
    const footer = document.createElement('footer');
    footer.className = 'app-footer';
    footer.innerHTML = `
      <div class="app-footer-inner">
        <p>&copy; 2026 EduSync &mdash; Student Record System.</p>
        <div class="app-footer-badges">
          <span class="app-footer-badge app-footer-badge-privacy" onclick="openPrivacyModal()">🔒 Privacy Policy</span>
          <span class="app-footer-badge" onclick="openHelpModal()">Need Help?</span>

          <span class="app-footer-badge">PHP</span>

          <span class="app-footer-badge">MySQL</span>
          <span class="app-footer-badge">HTML/CSS/JS</span>
          <span class="app-footer-badge">GDPR</span>
          <span class="app-footer-badge">Agile</span>
        </div>
      </div>
    `;
    const layout = document.querySelector('.app-layout');
    if (layout) layout.after(footer);
    else document.body.appendChild(footer);

    // ── Inject privacy modal into the page (once only) ──────
    if (!document.getElementById('privacyModal')) {
      injectPrivacyModal();
    }
  }

  // ── PRIVACY MODAL ─────────────────────────────────────────
  /**
   * Injects the privacy policy modal overlay into the document body.
   * Called once by buildFooter(); safe to call on any authenticated page.
   *
   * @return {void}
   */
  function injectPrivacyModal() {

    // ── Styles ───────────────────────────────────────────────
    const style = document.createElement('style');
    style.textContent = `
      .app-footer-badge-privacy {
        cursor: pointer;
        transition: background .15s, color .15s, border-color .15s;
      }
      .app-footer-badge-privacy:hover {
        background: #3b82f6 !important;
        color: #fff !important;
        border-color: #3b82f6 !important;
      }
      .priv-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.72);
        backdrop-filter: blur(4px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
      }
      .priv-overlay.open { display: flex; }
      .priv-modal {
        background: var(--surface, #1a2035);
        border: 1px solid var(--border, #2a3450);
        border-radius: 20px;
        width: 100%;
        max-width: 680px;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 32px 80px rgba(0,0,0,.5);
        overflow: hidden;
        color: var(--text, #e2e8f0);
      }
      .priv-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px 28px 18px;
        border-bottom: 1px solid var(--border, #2a3450);
        flex-shrink: 0;
      }
      .priv-modal-header h2 {
        font-size: 17px;
        font-weight: 800;
        color: var(--text, #e2e8f0);
        letter-spacing: -.02em;
        margin: 0;
      }
      .priv-modal-date {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        background: var(--surface2, #131929);
        padding: 4px 10px;
        border-radius: 20px;
        border: 1px solid var(--border, #2a3450);
        margin: 0 12px;
      }
      .priv-close {
        background: none;
        border: none;
        font-size: 22px;
        color: var(--text-muted, #64748b);
        cursor: pointer;
        line-height: 1;
        padding: 4px 6px;
        border-radius: 6px;
        transition: color .15s, background .15s;
        flex-shrink: 0;
      }
      .priv-close:hover {
        color: var(--text, #e2e8f0);
        background: var(--surface2, #131929);
      }
      .priv-modal-body {
        padding: 24px 28px;
        overflow-y: auto;
        flex: 1;
      }
      .priv-modal-body h3 {
        font-size: 13px;
        font-weight: 700;
        color: var(--text, #e2e8f0);
        margin: 20px 0 8px;
      }
      .priv-modal-body h3:first-child { margin-top: 0; }
      .priv-modal-body p {
        font-size: 13px;
        color: var(--text-muted, #94a3b8);
        line-height: 1.8;
        margin-bottom: 6px;
      }
      .priv-tag {
        display: inline-block;
        font-size: 11px;
        font-weight: 600;
        padding: 3px 10px;
        border-radius: 20px;
        background: rgba(59,130,246,.12);
        color: #60a5fa;
        border: 1px solid rgba(59,130,246,.25);
        margin: 2px 2px 2px 0;
      }
      .priv-divider {
        border: none;
        border-top: 1px solid var(--border, #2a3450);
        margin: 18px 0;
      }
      .priv-modal-footer {
        padding: 14px 28px;
        border-top: 1px solid var(--border, #2a3450);
        background: var(--surface2, #131929);
        font-size: 12px;
        color: var(--text-muted, #64748b);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
      }
    `;
    document.head.appendChild(style);

    // ── Modal HTML ───────────────────────────────────────────
    const modal = document.createElement('div');
    modal.className = 'priv-overlay';
    modal.id = 'privacyModal';
    modal.addEventListener('click', e => { if (e.target === modal) closePrivacyModal(); });

    modal.innerHTML = `
      <div class="priv-modal" role="dialog" aria-modal="true" aria-labelledby="privTitle">
        <div class="priv-modal-header">
          <h2 id="privTitle">🔒 Privacy Policy</h2>
          <span class="priv-modal-date">Last updated: May 2026</span>
          <button class="priv-close" onclick="closePrivacyModal()" aria-label="Close">&times;</button>
        </div>
        <div class="priv-modal-body">

          <h3>📋 Who we are</h3>
          <p>EduSync is a web-based student record system developed as part of CTEC2713 Agile Development at Niels Brock Copenhagen Business College. It is operated exclusively by authorised school staff.</p>

          <hr class="priv-divider">

          <h3>📦 What personal data we collect</h3>
          <p>EduSync stores the minimum personal data necessary to operate the system:</p>
          <p>
            <span class="priv-tag">Full name</span>
            <span class="priv-tag">Username</span>
            <span class="priv-tag">Role</span>
            <span class="priv-tag">Account status</span>
            <span class="priv-tag">Encrypted password</span>
            <span class="priv-tag">Account creation date</span>
          </p>
          <p>Passwords are never stored in plain text — they are protected using bcrypt one-way hashing. No email addresses, phone numbers, or other personal identifiers are collected.</p>

          <hr class="priv-divider">

          <h3>🎯 Why we collect it</h3>
          <p>Personal data is collected solely for <strong>school staff authentication and access management</strong>. It is not used for any other purpose.</p>

          <hr class="priv-divider">

          <h3>👁️ Who can see your data</h3>
          <p>Only users with the <strong>Administrator</strong> role can view, add, edit, deactivate, or delete staff records. Teachers and Headteachers cannot access staff management pages at all.</p>

          <hr class="priv-divider">

          <h3>🔒 How we protect your data</h3>
          <p>EduSync applies multiple technical security measures in line with GDPR Article 25 (data protection by design):</p>
          <p>
            <span class="priv-tag">Bcrypt password hashing</span>
            <span class="priv-tag">SQL injection protection</span>
            <span class="priv-tag">XSS prevention</span>
            <span class="priv-tag">Role-based access control</span>
            <span class="priv-tag">Session hardening</span>
            <span class="priv-tag">Brute-force lockout</span>
          </p>

          <hr class="priv-divider">

          <h3>⏱️ How long we keep your data</h3>
          <p>Staff records are retained for as long as the account is operationally required. When a staff member leaves, their account is <strong>deactivated</strong> rather than deleted, preserving the audit trail. Permanent deletion requires explicit Administrator confirmation.</p>

          <hr class="priv-divider">

          <h3>🌍 Your rights under GDPR</h3>
          <p>As a data subject under GDPR (EU) 2016/679, you have the following rights:</p>
          <p>
            <span class="priv-tag">Right to access your data</span>
            <span class="priv-tag">Right to correct inaccurate data</span>
            <span class="priv-tag">Right to erasure</span>
            <span class="priv-tag">Right to object to processing</span>
            <span class="priv-tag">Right to data portability</span>
          </p>
          <p>To exercise any of these rights, contact your school Administrator directly.</p>

          <hr class="priv-divider">

          <h3>🚫 What we do not do</h3>
          <p>EduSync does <strong>not</strong> sell, share, or transfer personal data to any third parties. No data is used for advertising, profiling, or automated decision-making.</p>

        </div>
        <div class="priv-modal-footer">
          <span>📄 In compliance with GDPR (EU) 2016/679</span>
          <span>Questions? Contact your school Administrator.</span>
        </div>
      </div>
    `;

    document.body.appendChild(modal);

    // Escape key closes the modal
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closePrivacyModal();
    });
  }

  /** Opens the privacy policy modal. @return {void} */
  window.openPrivacyModal  = () => document.getElementById('privacyModal')?.classList.add('open');

  /** Closes the privacy policy modal. @return {void} */
  window.closePrivacyModal = () => document.getElementById('privacyModal')?.classList.remove('open');

  // ── INIT ──────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    buildNav();
    buildSidebar();
    buildFooter();
  });

})();