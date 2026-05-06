<!DOCTYPE html>
<html lang="en" data-theme="system">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — EduSync</title>
<link rel="stylesheet" href="../shared/style.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<nav class="topnav" id="topnav"></nav>
<div class="app-layout">
  <aside class="sidebar" id="sidebar"></aside>
  <main class="content">

    <div class="page-eyebrow">Admin · Sujan Ghimire</div>
    <div class="page-title">Staff Management</div>
    <div class="page-sub">Manage school staff accounts, roles and access control.</div>

    <div class="toolbar">
      <div class="search-bar">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Search name, email or username…">
      </div>
      <select class="form-select" id="roleFilter" style="width:auto;min-width:140px;">
        <option value="">All Roles</option>
        <option value="Administrator">Administrator</option>
        <option value="Teacher">Teacher</option>
        <option value="Headteacher">Headteacher</option>
      </select>
      <select class="form-select" id="statusFilter" style="width:auto;min-width:130px;">
        <option value="">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
      <a href="add.php" class="btn btn-primary">+ Add Staff</a>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th><th>Full Name</th><th>Email</th><th>Username</th>
            <th>Role</th><th>Created</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody id="staffBody"></tbody>
      </table>
    </div>

  </main>
</div>

<!-- DELETE MODAL -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title">Confirm Delete</div>
      <button class="modal-close" onclick="closeOverlay('deleteModal')">✕</button>
    </div>
    <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:14px;">
      Permanently delete <strong id="deleteName"></strong>? This cannot be undone.
    </p>
    <div class="callout callout-warn">
      <span>⚠️</span>
      <span>Under GDPR, consider <strong>deactivating</strong> instead of deleting to preserve attendance audit history.</span>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeOverlay('deleteModal')">Cancel</button>
      <button class="btn btn-danger" onclick="confirmDelete()">Delete Permanently</button>
    </div>
  </div>
</div>

<script src="../shared/auth.js"></script>
<script src="script.js"></script>
</body>
</html>
