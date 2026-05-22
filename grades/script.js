/**
 * grades/script.js
 *
 * Handles filtering and toast messages
 * for the Grades page.
 *
 * @package EduSync
 * @author  Dibya Roshni Sahu
 */

/**
 * Function to filter the grades table
 * based on the search input.
 */
function filterTable() {

  // Get search input value and convert to lowercase
  const q =
    document
      .getElementById('searchInput')
      .value
      .toLowerCase();

  // Select all table rows with grade names
  document
    .querySelectorAll(
      '#gradeTable tbody tr[data-name]'
    )
    .forEach(row => {

      // Show row if search text matches grade name
      row.style.display =
        (!q || row.dataset.name.includes(q))
          ? ''
          : 'none';
    });
}

// Run filter function while typing in search box
document
  .getElementById('searchInput')
  .addEventListener(
    'input',
    filterTable
  );

/**
 * Auto-hide success or error toast message
 * after 4 seconds.
 */

// Get toast message element
const toast =
  document.getElementById('toastMsg');

// Hide toast after 4 seconds if it exists
if (toast)
  setTimeout(
    () => toast.style.display = 'none',
    4000
  );