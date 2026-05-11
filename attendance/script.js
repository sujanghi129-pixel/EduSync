/**
 * attendance/script.js
 *
 * Client-side behaviour for the Attendance marking and editing pages.
 *
 * Responsibilities:
 *  - Marks all students at once with a given status (Present/Late/Absent)
 *  - Shows or hides the Remarks/Notes input based on the selected status
 *  - Updates row highlight colour to reflect the current status
 *  - Handles the notes visibility toggle on the single-record edit page
 *
 * @package EduSync
 * @author  Laxman Giri
 */

// ── MARK ALL ──────────────────────────────────────────────────

/**
 * Sets all student rows to the same attendance status at once.
 * Called by the "All Present", "All Absent" and "All Late" quick buttons.
 *
 * @param  {string} status - The status to apply: 'present', 'absent' or 'late'.
 * @return {void}
 */
function markAll(status) {
  document.querySelectorAll('.att-row').forEach(row => {
    const radio = row.querySelector(`input[value="${status}"]`);
    if (radio) {
      radio.checked = true;
      updateRowStyle(row, status);
      toggleNotes(row, status);
    }
  });
}

// ── NOTES VISIBILITY ──────────────────────────────────────────

/**
 * Shows or hides the Remarks/Notes input field for a given student row
 * based on the selected attendance status.
 *
 * Notes are hidden for 'present' (no reason needed) and shown for
 * 'late' or 'absent' with a context-appropriate placeholder.
 *
 * @param  {HTMLElement} row    - The table row element for the student.
 * @param  {string}      status - The selected status: 'present', 'late' or 'absent'.
 * @return {void}
 */
function toggleNotes(row, status) {
  const notesInput = row.querySelector('.notes-input');
  if (!notesInput) return;

  if (status === 'present') {
    // Hide and clear notes — no reason needed when present
    notesInput.style.display = 'none';
    notesInput.value = '';
  } else {
    // Show notes with a relevant placeholder
    notesInput.style.display = '';
    notesInput.placeholder = status === 'late'
      ? 'Reason for being late'
      : 'Reason for absence';
  }
}

// ── RADIO BUTTON CHANGE HANDLER ───────────────────────────────

/**
 * Listen for status radio button changes on each student row.
 * Updates the row highlight colour, the active label style,
 * and the notes field visibility whenever a status is selected.
 *
 * @listens change
 */
document.querySelectorAll('.status-btn input[type="radio"]').forEach(radio => {
  radio.addEventListener('change', function () {
    const row   = this.closest('.att-row');
    const group = this.closest('.status-toggle');

    // Remove active class from all buttons in this group
    group.querySelectorAll('.status-btn').forEach(btn => {
      btn.classList.remove('active-present', 'active-late', 'active-absent');
    });

    // Add active class to the selected button
    this.closest('.status-btn').classList.add(`active-${this.value}`);

    if (row) {
      updateRowStyle(row, this.value);
      toggleNotes(row, this.value);
    }
  });
});

// ── EDIT PAGE: NOTES TOGGLE ───────────────────────────────────

/**
 * On the single-record edit page, show or hide the notes group
 * whenever the status radio button changes.
 *
 * @listens change
 */
document.querySelectorAll('input[name="status"]').forEach(radio => {
  radio.addEventListener('change', function () {
    const notesGroup = document.getElementById('notesGroup');
    if (notesGroup) {
      notesGroup.style.display = this.value === 'present' ? 'none' : '';
    }
  });
});

// ── ROW STYLE UPDATE ──────────────────────────────────────────

/**
 * Updates the background highlight colour of a student row to reflect
 * the currently selected attendance status.
 *
 * Also updates the active CSS class on the status toggle labels.
 *
 * @param  {HTMLElement} row    - The table row element to update.
 * @param  {string}      status - The selected status: 'present', 'late' or 'absent'.
 * @return {void}
 */
function updateRowStyle(row, status) {
  // Remove all existing status colour classes
  row.classList.remove('row-present', 'row-late', 'row-absent');

  // Add the class for the new status
  row.classList.add(`row-${status}`);

  // Reset all label active states within the row
  row.querySelectorAll('.status-btn').forEach(btn => {
    btn.classList.remove('active-present', 'active-late', 'active-absent');
  });

  // Highlight the active label
  const activeBtn = row.querySelector(`input[value="${status}"]`)?.closest('.status-btn');
  if (activeBtn) activeBtn.classList.add(`active-${status}`);
}

// ── TOAST AUTO-HIDE ───────────────────────────────────────────

/**
 * Auto-hide the success/error toast message after 4 seconds.
 *
 * @type {HTMLElement|null}
 */
const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => toast.style.display = 'none', 4000);
