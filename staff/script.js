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
 * Filters the staff table rows based on the current values of:
 * - Search input
 * - Role filter
 * - Status filter
 *
 * Each row stores searchable data in data-* attributes
 * (data-name, data-username, data-role, data-active)
 *
 * @return {void}
 */
function filterTable() {

  // Get current filter values
  const q      = document.getElementById('searchInput').value.toLowerCase();
  const role   = document.getElementById('roleFilter').value;
  const status = document.getElementById('statusFilter').value;

  // Loop through each staff row in the table
  document.querySelectorAll('#staffTable tbody tr[data-name]').forEach(row => {

    // Check if search text matches name or username
    const matchQ =
      !q ||
      row.dataset.name.includes(q) ||
      row.dataset.username.includes(q);

    // Check if selected role matches row role
    const matchRole =
      !role || row.dataset.role === role;

    // Check if selected status matches row status
    const matchStatus =
      !status || row.dataset.active === status;

    // Show or hide row depending on filter result
    row.style.display =
      (matchQ && matchRole && matchStatus) ? '' : 'none';
  });
}

/**
 * Attach event listeners for live filtering
 *
 * - input  → runs when user types in search box
 * - change → runs when dropdown selection changes
 */
document.getElementById('searchInput').addEventListener('input', filterTable);
document.getElementById('roleFilter').addEventListener('change', filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);

/**
 * Auto-hide success or error toast message after 4 seconds
 *
 * @type {HTMLElement|null}
 */
const toast = document.getElementById('toastMsg');

if (toast) {
  setTimeout(() => {
    toast.style.display = 'none';
  }, 4000);
}