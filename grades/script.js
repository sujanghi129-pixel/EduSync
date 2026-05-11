/**
 * grades/script.js
 *
 * Client-side filtering for the Grades list page.
 * Filters the grades table in real time based on the search input.
 *
 * @package EduSync
 * @author  Roshni Karki
 */

/**
 * Filters the grades table rows based on the current search input value.
 *
 * Each table row stores its grade name in the data-name attribute,
 * which is set by the PHP template.
 *
 * @return {void}
 */
function filterTable() {
  const q = document.getElementById('searchInput').value.toLowerCase();

  document.querySelectorAll('#gradeTable tbody tr[data-name]').forEach(row => {
    // Show row only if the grade name contains the search query
    row.style.display = (!q || row.dataset.name.includes(q)) ? '' : 'none';
  });
}

// Attach live filter listener to the search input
document.getElementById('searchInput').addEventListener('input', filterTable);

/**
 * Auto-hide the success/error toast message after 4 seconds.
 *
 * @type {HTMLElement|null}
 */
const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);
