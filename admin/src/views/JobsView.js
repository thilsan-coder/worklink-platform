import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  status: '',
  search: '',
  page: 1,
};

export function renderJobsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">📋 Job Operations & Lifecycle</h2>
        <div class="panel-actions">
          <input type="text" id="jobs-search" class="input-search" placeholder="Search title, customer, worker..." value="${state.search}" />
          <select id="jobs-status-filter" class="select-filter">
            <option value="" ${state.status === '' ? 'selected' : ''}>All Statuses</option>
            <option value="REQUESTED" ${state.status === 'REQUESTED' ? 'selected' : ''}>REQUESTED</option>
            <option value="ACCEPTED" ${state.status === 'ACCEPTED' ? 'selected' : ''}>ACCEPTED</option>
            <option value="SCHEDULED" ${state.status === 'SCHEDULED' ? 'selected' : ''}>SCHEDULED</option>
            <option value="IN_PROGRESS" ${state.status === 'IN_PROGRESS' ? 'selected' : ''}>IN_PROGRESS</option>
            <option value="COMPLETED" ${state.status === 'COMPLETED' ? 'selected' : ''}>COMPLETED</option>
            <option value="REJECTED" ${state.status === 'REJECTED' ? 'selected' : ''}>REJECTED</option>
            <option value="CANCELLED" ${state.status === 'CANCELLED' ? 'selected' : ''}>CANCELLED</option>
          </select>
        </div>
      </div>

      <div id="jobs-table-container">
        <div class="loading-state">Loading jobs...</div>
      </div>
    </div>
  `;
}

async function loadJobs() {
  const container = document.getElementById('jobs-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading jobs...</div>';

  try {
    const res = await Api.getJobs({
      status: state.status,
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const jobs = paginated.data || [];

    if (jobs.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">📋</div>
          <div>No jobs found matching criteria.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = jobs.map(j => {
      const c = j.customer || {};
      const w = j.worker || {};

      let statusBadge = `<span class="badge badge-secondary">${j.status}</span>`;
      if (j.status === 'COMPLETED') statusBadge = `<span class="badge badge-success">✓ COMPLETED</span>`;
      else if (['IN_PROGRESS', 'ACCEPTED', 'SCHEDULED'].includes(j.status)) statusBadge = `<span class="badge badge-info">${j.status}</span>`;
      else if (j.status === 'REQUESTED') statusBadge = `<span class="badge badge-warning">⏳ REQUESTED</span>`;
      else if (['CANCELLED', 'REJECTED'].includes(j.status)) statusBadge = `<span class="badge badge-danger">${j.status}</span>`;

      const amount = j.final_cost || j.estimated_cost || 0;

      return `
        <tr>
          <td><strong style="color: var(--text-muted); font-size: 11px;">${j.job_number || '#' + j.id}</strong></td>
          <td>
            <div style="font-weight: 600;">${j.title || 'Service Request'}</div>
            <div style="font-size: 11px; color: var(--text-muted);">${j.category?.name || 'General'}</div>
          </td>
          <td>${c.name || 'Customer'}</td>
          <td>${w.name || 'Worker'}</td>
          <td><strong>LKR ${Number(amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong></td>
          <td>${statusBadge}</td>
          <td>${new Date(j.created_at).toLocaleDateString()}</td>
          <td>
            <button class="btn btn-secondary btn-sm btn-view-job" data-id="${j.id}">
              Details
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
              <th>Job #</th>
              <th>Service Title</th>
              <th>Customer</th>
              <th>Worker</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${jobs.length}</strong> of <strong>${paginated.total || jobs.length}</strong> jobs</div>
        <div class="pagination-controls">
          <button id="jobs-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="jobs-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    container.querySelectorAll('.btn-view-job').forEach(btn => {
      btn.addEventListener('click', () => viewJobDetails(btn.dataset.id));
    });

    const prevBtn = document.getElementById('jobs-prev-page');
    const nextBtn = document.getElementById('jobs-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadJobs(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadJobs(); });

  } catch (err) {
    Toast.error('Failed to load jobs: ' + err.message);
  }
}

async function viewJobDetails(id) {
  try {
    const res = await Api.getJob(id);
    const j = res.data;

    const historyHtml = j.status_history?.map(h => `
      <div style="display: flex; justify-content: space-between; font-size: 12px; padding: 6px 0; border-bottom: 1px dashed var(--border-subtle);">
        <span><strong style="color: var(--primary);">${h.to_status}</strong> (from ${h.from_status || 'INIT'})</span>
        <span style="color: var(--text-muted);">${new Date(h.created_at).toLocaleString()}</span>
      </div>
    `).join('') || '<div style="font-size: 12px; color: var(--text-muted);">No history recorded</div>';

    Modal.open({
      title: `Job ${j.job_number || '#' + j.id} — ${j.title}`,
      contentHtml: `
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
            <div><strong>Customer:</strong> ${j.customer?.name || '—'} (${j.customer?.phone || '—'})</div>
            <div><strong>Worker:</strong> ${j.worker?.name || '—'} (${j.worker?.phone || '—'})</div>
            <div><strong>Category:</strong> ${j.category?.name || '—'}</div>
            <div><strong>Status:</strong> <span class="badge badge-info">${j.status}</span></div>
            <div><strong>Estimated Cost:</strong> LKR ${Number(j.estimated_cost || 0).toLocaleString()}</div>
            <div><strong>Final Cost:</strong> LKR ${Number(j.final_cost || 0).toLocaleString()}</div>
            <div style="grid-column: span 2;"><strong>Description:</strong> ${j.description || 'No description provided.'}</div>
          </div>

          <div style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
            <h4 style="margin-bottom: 8px; font-size: 13px; color: var(--text-muted);">⏳ Lifecycle History</h4>
            <div style="display: flex; flex-direction: column;">
              ${historyHtml}
            </div>
          </div>
        </div>
      `,
      footerButtons: [
        { label: 'Close', onClick: (_, m) => m.close() }
      ]
    });
  } catch (err) {
    Toast.error('Failed to load job details: ' + err.message);
  }
}

export function attachJobsEvents() {
  const searchInput = document.getElementById('jobs-search');
  const statusFilter = document.getElementById('jobs-status-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadJobs();
      }, 300);
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      state.status = e.target.value;
      state.page = 1;
      loadJobs();
    });
  }

  loadJobs();
}
