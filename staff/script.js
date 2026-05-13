/**
 * staff/script.js
 *
 * Client-side filtering for the Staff Management list page.
 * Filters the staff table in real time based on search input,
 * role dropdown and status dropdown — no page reload required.
 *
 * @package EduSync
 * @author  Sujan Ghimire
 */

/**
 * Filters the staff table rows based on the current values of the
 * search input, role filter and status filter.
 *
 * Each table row stores its searchable data in data-* attributes
 * (data-name, data-username, data-role, data-active)
 * which are set by the PHP template.
 *
 * @return {void}
 */
function filterTable() {
  const q      = document.getElementById('searchInput').value.toLowerCase();
  const role   = document.getElementById('roleFilter').value;
  const status = document.getElementById('statusFilter').value;

  document.querySelectorAll('#staffTable tbody tr[data-name]').forEach(row => {
    const matchQ      = !q      || row.dataset.name.includes(q) || row.dataset.username.includes(q);
    const matchRole   = !role   || row.dataset.role   === role;
    const matchStatus = !status || row.dataset.active === status;

    row.style.display = (matchQ && matchRole && matchStatus) ? '' : 'none';
  });
}

// Attach live filter listeners
document.getElementById('searchInput').addEventListener('input',   filterTable);
document.getElementById('roleFilter').addEventListener('change',   filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);

/**
 * Auto-hide the success/error toast message after 4 seconds.
 *
 * @type {HTMLElement|null}
 */
const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);
