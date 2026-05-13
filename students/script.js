/**
 * students/script.js
 *
 * Client-side filtering and dynamic class dropdown for the Students pages.
 *
 * Responsibilities:
 *  - Filters the students table in real time by name, grade, class and status
 *  - Dynamically populates the class dropdown based on the selected grade
 *    by fetching matching classes from get_classes.php via AJAX
 *
 * @package EduSync
 * @author  Susma pandey
 */

// ── TABLE FILTER ─────────────────────────────────────────────

/**
 * Filters the students table rows based on the current values of the
 * search input, grade filter, class filter and status filter.
 *
 * Each table row stores its searchable data in data-* attributes
 * (data-name, data-grade, data-class, data-active) set by the PHP template.
 *
 * @return {void}
 */
function filterTable() {
  const q      = document.getElementById('searchInput')?.value.toLowerCase() ?? '';
  const grade  = document.getElementById('gradeFilter')?.value  ?? '';
  const cls    = document.getElementById('classFilter')?.value  ?? '';
  const status = document.getElementById('statusFilter')?.value ?? '';

  document.querySelectorAll('#studentTable tbody tr[data-name]').forEach(row => {
    const matchQ      = !q      || row.dataset.name.includes(q);
    const matchGrade  = !grade  || row.dataset.grade === grade;
    const matchClass  = !cls    || row.dataset.class === cls;
    const matchStatus = !status || row.dataset.active === status;

    // Show row only if all filters match
    row.style.display = (matchQ && matchGrade && matchClass && matchStatus) ? '' : 'none';
  });
}

// Attach live filter listeners — guard with null checks as these
// elements only exist on index.php, not on add/edit pages
const searchInput  = document.getElementById('searchInput');
const gradeFilter  = document.getElementById('gradeFilter');
const classFilter  = document.getElementById('classFilter');
const statusFilter = document.getElementById('statusFilter');

if (searchInput)  searchInput.addEventListener('input',   filterTable);
if (gradeFilter)  gradeFilter.addEventListener('change',  filterTable);
if (classFilter)  classFilter.addEventListener('change',  filterTable);
if (statusFilter) statusFilter.addEventListener('change', filterTable);

// ── DYNAMIC CLASS DROPDOWN ────────────────────────────────────

/**
 * Grade select element on the add/edit form.
 * Only present on add.php and edit.php.
 *
 * @type {HTMLSelectElement|null}
 */
const gradeSelect = document.getElementById('gradeSelect');

/**
 * Class select element on the add/edit form.
 * Only present on add.php and edit.php.
 *
 * @type {HTMLSelectElement|null}
 */
const classSelect = document.getElementById('classSelect');

if (gradeSelect && classSelect) {
  /**
   * When the grade dropdown changes, fetch the matching active classes
   * from the server and repopulate the class dropdown dynamically.
   *
   * Uses the get_classes.php AJAX endpoint which returns JSON.
   *
   * @listens change
   * @param   {Event} event - The change event from the grade select element.
   * @return  {Promise<void>}
   */
  gradeSelect.addEventListener('change', async function () {
    const gradeId = this.value;

    // Reset class dropdown while loading
    classSelect.innerHTML = '<option value="">Loading…</option>';

    if (!gradeId) {
      classSelect.innerHTML = '<option value="">Select grade first…</option>';
      return;
    }

    try {
      /**
       * Fetch classes for the selected grade from the server.
       *
       * @type {Response}
       */
      const res  = await fetch(`get_classes.php?gradeId=${gradeId}`);

      /**
       * Parsed JSON array of class objects: [{ classId, className }, ...]
       *
       * @type {Array<{classId: number, className: string}>}
       */
      const data = await res.json();

      if (data.length === 0) {
        classSelect.innerHTML = '<option value="">No classes in this grade</option>';
      } else {
        classSelect.innerHTML =
          '<option value="">Select class…</option>' +
          data.map(c => `<option value="${c.classId}">${c.className}</option>`).join('');
      }
    } catch (err) {
      // Show a fallback message if the AJAX request fails
      classSelect.innerHTML = '<option value="">Error loading classes</option>';
      console.error('Failed to load classes:', err);
    }
  });
}

// ── TOAST AUTO-HIDE ───────────────────────────────────────────

/**
 * Auto-hide the success/error toast message after 4 seconds.
 *
 * @type {HTMLElement|null}
 */
const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);
