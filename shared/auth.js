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
      <div class="nav-logo">ES</div>
      <div class="nav-brand">EduSync</div>
      <div class="nav-spacer"></div>
      <div class="nav-right">
        <div class="nav-avatar" title="${esc(user.fullName || 'EduSync')}">${initials}</div>
        <a href="${url('logout.php')}" class="btn-signout">Sign out</a>
      </div>
    `;

    document.getElementById('hamburger')?.addEventListener('click', toggleSidebar);
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

  // ── INIT ──────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    buildNav();
    buildSidebar();
  });

})();