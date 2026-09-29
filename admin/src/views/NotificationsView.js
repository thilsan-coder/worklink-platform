import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';

let state = {
  type: '',
  is_read: '',
  search: '',
  page: 1,
};

export function renderNotificationsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">🔔 Platform Notification Stream</h2>
        <div class="panel-actions">
          <input type="text" id="notif-search" class="input-search" placeholder="Search title, user..." value="${state.search}" />
          <select id="notif-read-filter" class="select-filter">
            <option value="" ${state.is_read === '' ? 'selected' : ''}>All Statuses</option>
            <option value="false" ${state.is_read === 'false' ? 'selected' : ''}>Unread</option>
            <option value="true" ${state.is_read === 'true' ? 'selected' : ''}>Read</option>
          </select>
        </div>
      </div>

      <div id="notif-table-container">
        <div class="loading-state">Loading notifications...</div>
      </div>
    </div>
  `;
}

async function loadNotifications() {
  const container = document.getElementById('notif-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading notifications...</div>';

  try {
    const res = await Api.getNotifications({
      type: state.type,
      is_read: state.is_read,
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const notifs = paginated.data || [];

    if (notifs.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">🔔</div>
          <div>No platform notifications found.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = notifs.map(n => {
      const u = n.user || {};
      const isReadBadge = n.is_read
        ? '<span class="badge badge-secondary">Read</span>'
        : '<span class="badge badge-info">Unread</span>';

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${n.id}</strong></td>
          <td>
            <div style="font-weight: 600;">${u.name || 'User #' + n.user_id}</div>
            <div style="font-size: 11px; color: var(--text-muted);">${u.phone || u.email || '—'}</div>
          </td>
          <td><strong style="color: var(--text-main);">${n.title || 'Notification'}</strong></td>
          <td><div style="max-width: 320px; font-size: 12px; color: var(--text-muted); line-height: 1.4;">${n.body || n.message || '—'}</div></td>
          <td><span class="badge badge-warning" style="font-size: 10px;">${n.type || 'SYSTEM'}</span></td>
          <td>${isReadBadge}</td>
          <td>${new Date(n.created_at).toLocaleString()}</td>
        </tr>
      `;
    }).join('');

    container.innerHTML = `
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Recipient</th>
              <th>Title</th>
              <th>Message Body</th>
              <th>Type</th>
              <th>Read Status</th>
              <th>Dispatched Date</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${notifs.length}</strong> of <strong>${paginated.total || notifs.length}</strong> notifications</div>
        <div class="pagination-controls">
          <button id="notif-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="notif-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    const prevBtn = document.getElementById('notif-prev-page');
    const nextBtn = document.getElementById('notif-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadNotifications(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadNotifications(); });

  } catch (err) {
    Toast.error('Failed to load notifications: ' + err.message);
  }
}

export function attachNotificationsEvents() {
  const searchInput = document.getElementById('notif-search');
  const readFilter = document.getElementById('notif-read-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadNotifications();
      }, 300);
    });
  }

  if (readFilter) {
    readFilter.addEventListener('change', (e) => {
      state.is_read = e.target.value;
      state.page = 1;
      loadNotifications();
    });
  }

  loadNotifications();
}
