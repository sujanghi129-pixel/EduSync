/**
 * classes/script.js
 *
 * Client-side filtering for the Classes list page.
 * Filters the classes table in real time based on search input,
 * grade dropdown and status dropdown — no page reload required.
 *
 * @package EduSync
 * @author  Saimon
 */

/**
 * Filters the classes table rows based on the current values of the
 * search input, grade filter and status filter.
 *
 * Each table row stores its searchable data in data-* attributes
 * (data-name, data-teacher, data-grade, data-active)
 * which are set by the PHP template.
 *
 * @return {void}
 */
function filterTable() {
  const q      = document.getElementById('searchInput').value.toLowerCase();
  const grade  = document.getElementById('gradeFilter').value;
  const status = document.getElementById('statusFilter').value;

  document.querySelectorAll('#classTable tbody tr[data-name]').forEach(row => {
    const matchQ      = !q      || row.dataset.name.includes(q) || row.dataset.teacher.includes(q);
    const matchGrade  = !grade  || row.dataset.grade  === grade;
    const matchStatus = !status || row.dataset.active === status;

    // Show row only if all filters match
    row.style.display = (matchQ && matchGrade && matchStatus) ? '' : 'none';
  });
}

// Attach live filter listeners to all filter controls
document.getElementById('searchInput').addEventListener('input',  filterTable);
document.getElementById('gradeFilter').addEventListener('change', filterTable);
document.getElementById('statusFilter').addEventListener('change', filterTable);

/**
 * Auto-hide the success/error toast message after 4 seconds.
 *
 * @type {HTMLElement|null}
 */
const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);
