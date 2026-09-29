import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';

let state = {
  action: '',
  entity_type: '',
  page: 1,
};

export function renderAuditLogsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">📜 Administrative Audit Logs</h2>
        <div class="panel-actions">
          <select id="audit-action-filter" class="select-filter">
            <option value="" ${state.action === '' ? 'selected' : ''}>All Actions</option>
            <option value="USER_SUSPENDED" ${state.action === 'USER_SUSPENDED' ? 'selected' : ''}>USER_SUSPENDED</option>
            <option value="USER_ACTIVATED" ${state.action === 'USER_ACTIVATED' ? 'selected' : ''}>USER_ACTIVATED</option>
            <option value="VERIFICATION_APPROVED" ${state.action === 'VERIFICATION_APPROVED' ? 'selected' : ''}>VERIFICATION_APPROVED</option>
            <option value="VERIFICATION_REJECTED" ${state.action === 'VERIFICATION_REJECTED' ? 'selected' : ''}>VERIFICATION_REJECTED</option>
            <option value="REVIEW_DELETED" ${state.action === 'REVIEW_DELETED' ? 'selected' : ''}>REVIEW_DELETED</option>
          </select>
        </div>
      </div>

      <div id="audit-table-container">
        <div class="loading-state">Loading audit logs...</div>
      </div>
    </div>
  `;
}

async function loadAuditLogs() {
  const container = document.getElementById('audit-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading audit logs...</div>';

  try {
    const res = await Api.getAuditLogs({
      action: state.action,
      entity_type: state.entity_type,
      page: state.page,
    });

    const paginated = res.data || {};
    const logs = paginated.data || [];

    if (logs.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">📜</div>
          <div>No administrative audit records logged yet.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = logs.map(l => {
      const admin = l.admin_user || {};

      let actionBadge = `<span class="badge badge-secondary">${l.action}</span>`;
      if (l.action.includes('APPROVED') || l.action.includes('ACTIVATED')) {
        actionBadge = `<span class="badge badge-success">${l.action}</span>`;
      } else if (l.action.includes('REJECTED') || l.action.includes('SUSPENDED') || l.action.includes('DELETED')) {
        actionBadge = `<span class="badge badge-danger">${l.action}</span>`;
      }

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${l.id}</strong></td>
          <td>${actionBadge}</td>
          <td>
            <div style="font-weight: 600;">${admin.name || 'Admin #' + (l.admin_user_id || 'System')}</div>
            <div style="font-size: 11px; color: var(--text-muted);">${admin.username || 'System'}</div>
          </td>
          <td><span class="badge badge-info">${l.entity_type} #${l.entity_id || '—'}</span></td>
          <td><div style="max-width: 340px; font-size: 12px; color: var(--text-main);">${l.description || '—'}</div></td>
          <td><code style="font-size: 11px; color: var(--text-muted);">${l.ip_address || '127.0.0.1'}</code></td>
          <td>${new Date(l.created_at).toLocaleString()}</td>
        </tr>
      `;
    }).join('');

    container.innerHTML = `
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Action</th>
              <th>Admin</th>
              <th>Entity</th>
              <th>Description</th>
              <th>IP Address</th>
              <th>Timestamp</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${logs.length}</strong> of <strong>${paginated.total || logs.length}</strong> audit records</div>
        <div class="pagination-controls">
          <button id="audit-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="audit-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    const prevBtn = document.getElementById('audit-prev-page');
    const nextBtn = document.getElementById('audit-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadAuditLogs(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadAuditLogs(); });

  } catch (err) {
    Toast.error('Failed to load audit logs: ' + err.message);
  }
}

export function attachAuditLogsEvents() {
  const actionFilter = document.getElementById('audit-action-filter');

  if (actionFilter) {
    actionFilter.addEventListener('change', (e) => {
      state.action = e.target.value;
      state.page = 1;
      loadAuditLogs();
    });
  }

  loadAuditLogs();
}
