import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  search: '',
  role: '',
  status: '',
  page: 1,
};

export function renderUsersView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">👥 User Management</h2>
        <div class="panel-actions">
          <input type="text" id="users-search" class="input-search" placeholder="Search name, email, phone..." value="${state.search}" />
          <select id="users-role-filter" class="select-filter">
            <option value="" ${state.role === '' ? 'selected' : ''}>All Roles</option>
            <option value="customer" ${state.role === 'customer' ? 'selected' : ''}>Customers</option>
            <option value="worker" ${state.role === 'worker' ? 'selected' : ''}>Workers</option>
            <option value="both" ${state.role === 'both' ? 'selected' : ''}>Both</option>
          </select>
          <select id="users-status-filter" class="select-filter">
            <option value="" ${state.status === '' ? 'selected' : ''}>All Statuses</option>
            <option value="active" ${state.status === 'active' ? 'selected' : ''}>Active</option>
            <option value="suspended" ${state.status === 'suspended' ? 'selected' : ''}>Suspended</option>
          </select>
        </div>
      </div>

      <div id="users-table-container">
        <div class="loading-state">Loading users...</div>
      </div>
    </div>
  `;
}

async function loadUsers() {
  const container = document.getElementById('users-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading users...</div>';

  try {
    const res = await Api.getUsers({
      search: state.search,
      role: state.role,
      status: state.status,
      page: state.page,
    });

    const paginated = res.data || {};
    const users = paginated.data || [];

    if (users.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">🔍</div>
          <div>No users found matching your filters.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = users.map(u => {
      const isSuspended = u.status === 'suspended';
      const statusBadge = isSuspended
        ? '<span class="badge badge-danger">Suspended</span>'
        : '<span class="badge badge-success">Active</span>';

      const roleBadge = u.role === 'worker'
        ? '<span class="badge badge-info">Worker</span>'
        : u.role === 'customer'
        ? '<span class="badge badge-secondary">Customer</span>'
        : '<span class="badge badge-warning">Both</span>';

      const dateStr = new Date(u.created_at).toLocaleDateString();

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${u.id}</strong></td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                ${(u.name || 'U')[0].toUpperCase()}
              </div>
              <div>
                <div style="font-weight: 600;">${u.name || 'No Name'}</div>
                <div style="font-size: 11px; color: var(--text-muted);">${u.email || 'No email'}</div>
              </div>
            </div>
          </td>
          <td>${u.phone || '—'}</td>
          <td>${roleBadge}</td>
          <td>${statusBadge}</td>
          <td>${dateStr}</td>
          <td>
            <div style="display: flex; gap: 6px;">
              <button class="btn btn-secondary btn-sm btn-view-user" data-id="${u.id}">Details</button>
              <button class="btn ${isSuspended ? 'btn-success' : 'btn-danger'} btn-sm btn-toggle-status" data-id="${u.id}" data-status="${u.status}" data-name="${u.name || 'User'}">
                ${isSuspended ? 'Activate' : 'Suspend'}
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    container.innerHTML = `
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>User</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${users.length}</strong> of <strong>${paginated.total || users.length}</strong> users</div>
        <div class="pagination-controls">
          <button id="users-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="users-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    // Attach row events
    container.querySelectorAll('.btn-view-user').forEach(btn => {
      btn.addEventListener('click', () => viewUserDetails(btn.dataset.id));
    });

    container.querySelectorAll('.btn-toggle-status').forEach(btn => {
      btn.addEventListener('click', () => toggleUserStatus(btn.dataset.id, btn.dataset.status, btn.dataset.name));
    });

    const prevBtn = document.getElementById('users-prev-page');
    const nextBtn = document.getElementById('users-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadUsers(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadUsers(); });

  } catch (err) {
    Toast.error('Failed to load users: ' + err.message);
  }
}

async function viewUserDetails(id) {
  try {
    const res = await Api.getUser(id);
    const u = res.data;

    const custJobsCount = u.customer_jobs?.length || 0;
    const workerJobsCount = u.worker_jobs?.length || 0;
    const wp = u.worker_profile;

    const skillsHtml = wp?.skills?.map(s => `<span class="badge badge-info">${s.name}</span>`).join(' ') || 'None';

    Modal.open({
      title: `User #${u.id} — ${u.name || 'User Profile'}`,
      contentHtml: `
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
            <div><strong>Email:</strong> ${u.email || '—'}</div>
            <div><strong>Phone:</strong> ${u.phone || '—'}</div>
            <div><strong>Role:</strong> ${u.role}</div>
            <div><strong>Status:</strong> ${u.status === 'active' ? '🟢 Active' : '🔴 Suspended'}</div>
            <div><strong>Created:</strong> ${new Date(u.created_at).toLocaleString()}</div>
            <div><strong>Customer Jobs:</strong> ${custJobsCount}</div>
          </div>

          ${wp ? `
            <div style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
              <h4 style="margin-bottom: 8px; font-size: 14px; color: var(--secondary);">🔧 Worker Profile</h4>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
                <div><strong>Hourly Rate:</strong> LKR ${wp.hourly_rate || '0.00'}</div>
                <div><strong>Experience:</strong> ${wp.experience_years || 0} yrs</div>
                <div><strong>Rating:</strong> ⭐ ${wp.average_rating || '5.0'} (${wp.total_reviews || 0} reviews)</div>
                <div><strong>Verification:</strong> ${wp.verification_status}</div>
                <div style="grid-column: span 2;"><strong>Bio:</strong> ${wp.bio || 'No bio'}</div>
                <div style="grid-column: span 2;"><strong>Skills:</strong> ${skillsHtml}</div>
              </div>
            </div>
          ` : ''}
        </div>
      `,
      footerButtons: [
        { label: 'Close', onClick: (_, m) => m.close() }
      ]
    });
  } catch (err) {
    Toast.error('Failed to load user details: ' + err.message);
  }
}

function toggleUserStatus(id, currentStatus, userName) {
  const isSuspended = currentStatus === 'suspended';
  const newStatus = isSuspended ? 'active' : 'suspended';
  const actionName = isSuspended ? 'Activate' : 'Suspend';

  Modal.open({
    title: `${actionName} User — ${userName}`,
    contentHtml: `
      <div style="font-size: 13px;">
        <p style="margin-bottom: 12px;">Are you sure you want to <strong>${actionName.toLowerCase()}</strong> user <strong>#${id} (${userName})</strong>?</p>
        ${!isSuspended ? `
          <div class="form-group">
            <label class="form-label" for="suspend-reason">Reason for Suspension (Optional):</label>
            <textarea id="suspend-reason" class="input-textarea" rows="3" placeholder="Enter reason for audit logs..."></textarea>
          </div>
        ` : ''}
      </div>
    `,
    footerButtons: [
      { label: 'Cancel', onClick: (_, m) => m.close() },
      {
        label: `Confirm ${actionName}`,
        className: isSuspended ? 'btn-success' : 'btn-danger',
        onClick: async (_, m) => {
          const reasonInput = document.getElementById('suspend-reason');
          const reason = reasonInput ? reasonInput.value.trim() : '';

          try {
            await Api.updateUserStatus(id, newStatus, reason);
            Toast.success(`User #${id} is now ${newStatus}.`);
            m.close();
            loadUsers();
          } catch (err) {
            Toast.error('Action failed: ' + err.message);
          }
        }
      }
    ]
  });
}

export function attachUsersEvents() {
  const searchInput = document.getElementById('users-search');
  const roleFilter = document.getElementById('users-role-filter');
  const statusFilter = document.getElementById('users-status-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadUsers();
      }, 300);
    });
  }

  if (roleFilter) {
    roleFilter.addEventListener('change', (e) => {
      state.role = e.target.value;
      state.page = 1;
      loadUsers();
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      state.status = e.target.value;
      state.page = 1;
      loadUsers();
    });
  }

  loadUsers();
}
