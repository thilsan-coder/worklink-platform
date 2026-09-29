import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  search: '',
  verification_status: '',
  min_rating: '',
  page: 1,
};

export function renderWorkersView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">🔧 Worker Directory & Management</h2>
        <div class="panel-actions">
          <input type="text" id="workers-search" class="input-search" placeholder="Search worker name, email..." value="${state.search}" />
          <select id="workers-verif-filter" class="select-filter">
            <option value="" ${state.verification_status === '' ? 'selected' : ''}>All Verifications</option>
            <option value="verified" ${state.verification_status === 'verified' ? 'selected' : ''}>Verified</option>
            <option value="pending" ${state.verification_status === 'pending' ? 'selected' : ''}>Pending</option>
            <option value="rejected" ${state.verification_status === 'rejected' ? 'selected' : ''}>Rejected</option>
            <option value="unverified" ${state.verification_status === 'unverified' ? 'selected' : ''}>Unverified</option>
          </select>
          <select id="workers-rating-filter" class="select-filter">
            <option value="" ${state.min_rating === '' ? 'selected' : ''}>All Ratings</option>
            <option value="4" ${state.min_rating === '4' ? 'selected' : ''}>4★ and above</option>
            <option value="4.5" ${state.min_rating === '4.5' ? 'selected' : ''}>4.5★ and above</option>
          </select>
        </div>
      </div>

      <div id="workers-table-container">
        <div class="loading-state">Loading workers...</div>
      </div>
    </div>
  `;
}

async function loadWorkers() {
  const container = document.getElementById('workers-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading workers...</div>';

  try {
    const res = await Api.getWorkers({
      search: state.search,
      verification_status: state.verification_status,
      min_rating: state.min_rating,
      page: state.page,
    });

    const paginated = res.data || {};
    const workers = paginated.data || [];

    if (workers.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">🔧</div>
          <div>No worker profiles found matching criteria.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = workers.map(w => {
      const u = w.user || {};
      const statusBadge = w.verification_status === 'verified'
        ? '<span class="badge badge-success">✓ Verified</span>'
        : w.verification_status === 'pending'
        ? '<span class="badge badge-warning">⏳ Pending</span>'
        : w.verification_status === 'rejected'
        ? '<span class="badge badge-danger">✗ Rejected</span>'
        : '<span class="badge badge-secondary">Unverified</span>';

      const skillsList = w.skills?.map(s => `<span class="badge badge-info" style="font-size: 10px;">${s.name}</span>`).slice(0, 3).join(' ') || '—';

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${w.id}</strong></td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 12px; background: #1E3A8A;">
                ${(u.name || 'W')[0].toUpperCase()}
              </div>
              <div>
                <div style="font-weight: 600;">${u.name || 'Worker'}</div>
                <div style="font-size: 11px; color: var(--text-muted);">${u.phone || u.email || '—'}</div>
              </div>
            </div>
          </td>
          <td>${skillsList}</td>
          <td><strong>LKR ${Number(w.hourly_rate || 0).toLocaleString()}</strong> / hr</td>
          <td><span style="color: #FBBF24; font-weight: 700;">⭐ ${w.average_rating || '5.0'}</span> <span style="font-size: 11px; color: var(--text-muted);">(${w.total_reviews || 0})</span></td>
          <td>${statusBadge}</td>
          <td>
            <button class="btn btn-secondary btn-sm btn-view-worker" data-id="${w.id}">
              Inspect
            </button>
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
              <th>Worker Name</th>
              <th>Skills</th>
              <th>Rate</th>
              <th>Rating</th>
              <th>Verification</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${workers.length}</strong> of <strong>${paginated.total || workers.length}</strong> workers</div>
        <div class="pagination-controls">
          <button id="workers-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="workers-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    container.querySelectorAll('.btn-view-worker').forEach(btn => {
      btn.addEventListener('click', () => viewWorkerDetails(btn.dataset.id));
    });

    const prevBtn = document.getElementById('workers-prev-page');
    const nextBtn = document.getElementById('workers-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadWorkers(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadWorkers(); });

  } catch (err) {
    Toast.error('Failed to load workers: ' + err.message);
  }
}

async function viewWorkerDetails(id) {
  try {
    const res = await Api.getWorker(id);
    const w = res.data;
    const u = w.user || {};

    const docsHtml = w.documents?.map(d => `
      <div style="padding: 8px 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-weight: 600; font-size: 12px;">${d.document_type || 'ID Document'}</div>
          <div style="font-size: 11px; color: var(--text-muted);">${d.file_path}</div>
        </div>
        <span class="badge ${d.status === 'approved' ? 'badge-success' : d.status === 'rejected' ? 'badge-danger' : 'badge-warning'}">${d.status}</span>
      </div>
    `).join('') || '<div style="color: var(--text-muted); font-size: 12px;">No documents uploaded</div>';

    Modal.open({
      title: `Worker Profile — ${u.name || 'Worker #' + w.id}`,
      contentHtml: `
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
            <div><strong>Experience:</strong> ${w.experience_years || 0} years</div>
            <div><strong>Hourly Rate:</strong> LKR ${Number(w.hourly_rate || 0).toLocaleString()}</div>
            <div><strong>Service Area:</strong> ${w.service_area_radius_km || 10} km radius</div>
            <div><strong>Completed Jobs:</strong> ${w.completed_jobs_count || 0}</div>
            <div><strong>Rating:</strong> ⭐ ${w.average_rating || '5.0'} (${w.total_reviews || 0} reviews)</div>
            <div><strong>Verification:</strong> ${w.verification_status}</div>
            <div style="grid-column: span 2;"><strong>Bio:</strong> ${w.bio || 'No bio provided.'}</div>
          </div>

          <div style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
            <h4 style="margin-bottom: 8px; font-size: 13px; color: var(--text-muted);">📄 Verification Documents</h4>
            <div style="display: flex; flex-direction: column; gap: 8px;">
              ${docsHtml}
            </div>
          </div>
        </div>
      `,
      footerButtons: [
        { label: 'Close', onClick: (_, m) => m.close() }
      ]
    });
  } catch (err) {
    Toast.error('Failed to load worker details: ' + err.message);
  }
}

export function attachWorkersEvents() {
  const searchInput = document.getElementById('workers-search');
  const verifFilter = document.getElementById('workers-verif-filter');
  const ratingFilter = document.getElementById('workers-rating-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadWorkers();
      }, 300);
    });
  }

  if (verifFilter) {
    verifFilter.addEventListener('change', (e) => {
      state.verification_status = e.target.value;
      state.page = 1;
      loadWorkers();
    });
  }

  if (ratingFilter) {
    ratingFilter.addEventListener('change', (e) => {
      state.min_rating = e.target.value;
      state.page = 1;
      loadWorkers();
    });
  }

  loadWorkers();
}
