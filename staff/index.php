# Frontend Only — `index.php`

```html
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Management — EduSync</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="overlay" id="overlay"></div>

<nav class="topnav" id="topnav">
  <div class="nav-logo">E</div>
  <div class="nav-brand">EduSync</div>
</nav>

<div class="app-layout">

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-label">Management</div>
    <a href="#" class="sidebar-link active">
      <span class="si">👥</span>
      Staff
    </a>
    <a href="#" class="sidebar-link">
      <span class="si">🎓</span>
      Students
    </a>
    <a href="#" class="sidebar-link">
      <span class="si">📚</span>
      Courses
    </a>
  </aside>

  <main class="content">

    <div class="page-eyebrow">Administrator · Sujan Ghimire</div>
    <div class="page-title">Staff Management</div>
    <div class="page-sub">
      Manage school staff accounts, roles and access control.
    </div>

    <div class="callout callout-success">
      ✅ Staff record loaded successfully.
    </div>

    <div class="toolbar">

      <div class="search-bar">
        <span>🔍</span>
        <input type="text" placeholder="Search name or username…">
      </div>

      <select class="form-select" style="width:auto;min-width:140px;">
        <option>All Roles</option>
        <option>Administrator</option>
        <option>Teacher</option>
        <option>Headteacher</option>
      </select>

      <select class="form-select" style="width:auto;min-width:130px;">
        <option>All Status</option>
        <option>Active</option>
        <option>Inactive</option>
      </select>

      <a href="#" class="btn btn-primary">+ Add Staff</a>

    </div>

    <div class="table-wrap">
      <table>

        <thead>
          <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Role</th>
            <th>Created</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>

          <tr>
            <td><code>1</code></td>
            <td><strong>John Carter</strong></td>
            <td><code>jcarter</code></td>
            <td>
              <span class="badge badge-blue">Administrator</span>
            </td>
            <td>2026-05-08</td>
            <td>
              <span class="badge badge-green">Active</span>
            </td>
            <td>
              <div class="action-row">
                <button class="btn btn-sm btn-ghost">Edit</button>
                <button class="btn btn-sm btn-danger">Delete</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><code>2</code></td>
            <td><strong>Sarah Wilson</strong></td>
            <td><code>swilson</code></td>
            <td>
              <span class="badge badge-yellow">Teacher</span>
            </td>
            <td>2026-05-06</td>
            <td>
              <span class="badge badge-red">Inactive</span>
            </td>
            <td>
              <div class="action-row">
                <button class="btn btn-sm btn-ghost">Edit</button>
                <button class="btn btn-sm btn-danger">Delete</button>
              </div>
            </td>
          </tr>

          <tr>
            <td><code>3</code></td>
            <td><strong>Michael Brown</strong></td>
            <td><code>mbrown</code></td>
            <td>
              <span class="badge badge-green">Headteacher</span>
            </td>
            <td>2026-05-01</td>
            <td>
              <span class="badge badge-green">Active</span>
            </td>
            <td>
              <div class="action-row">
                <button class="btn btn-sm btn-ghost">Edit</button>
                <button class="btn btn-sm btn-danger">Delete</button>
              </div>
            </td>
          </tr>

        </tbody>

      </table>
    </div>

  </main>
</div>

</body>
</html>
```
