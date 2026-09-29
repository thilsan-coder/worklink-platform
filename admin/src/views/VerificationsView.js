import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  status: 'pending',
  search: '',
  page: 1,
};

export function renderVerificationsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">🛡️ Worker Verification Hub</h2>
        <div class="panel-actions">
          <input type="text" id="verif-search" class="input-search" placeholder="Search worker name..." value="${state.search}" />
          <select id="verif-status-filter" class="select-filter">
            <option value="pending" ${state.status === 'pending' ? 'selected' : ''}>Pending (Awaiting Review)</option>
            <option value="verified" ${state.status === 'verified' ? 'selected' : ''}>Approved / Verified</option>
            <option value="rejected" ${state.status === 'rejected' ? 'selected' : ''}>Rejected</option>
            <option value="" ${state.status === '' ? 'selected' : ''}>All</option>
          </select>
        </div>
      </div>

      <div id="verif-table-container">
        <div class="loading-state">Loading verification requests...</div>
      </div>
    </div>
  `;
}

async function loadVerifications() {
  const container = document.getElementById('verif-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading verification requests...</div>';

  try {
    const res = await Api.getVerifications({
      status: state.status,
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const items = paginated.data || [];

    if (items.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">🛡️</div>
          <div>No verification requests found for this filter.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = items.map(w => {
      const u = w.user || {};
      const docs = w.documents || [];
      const isPending = w.verification_status === 'pending';
      const isVerified = w.verification_status === 'verified';
      const isRejected = w.verification_status === 'rejected';

      const statusBadge = isVerified
        ? '<span class="badge badge-success">✓ Verified</span>'
        : isPending
        ? '<span class="badge badge-warning">⏳ Pending</span>'
        : isRejected
        ? '<span class="badge badge-danger">✗ Rejected</span>'
        : '<span class="badge badge-secondary">Unverified</span>';

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${w.id}</strong></td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
              <div class="admin-avatar" style="width: 32px; height: 32px; font-size: 12px; background: #3730A3;">
                ${(u.name || 'W')[0].toUpperCase()}
              </div>
              <div>
                <div style="font-weight: 600;">${u.name || 'Worker'}</div>
                <div style="font-size: 11px; color: var(--text-muted);">${u.phone || u.email || '—'}</div>
              </div>
            </div>
          </td>
          <td>
            <span style="font-size: 12px; color: var(--text-main); font-weight: 600;">${docs.length} Document(s)</span>
            ${docs.map(d => `<div style="font-size: 10px; color: var(--text-subtle);">${d.document_type} (${d.status})</div>`).join('')}
          </td>
          <td>${statusBadge}</td>
          <td>
            <div style="font-size: 12px;">${new Date(w.created_at).toLocaleDateString()}</div>
            ${w.verification_rejection_reason ? `<div style="font-size: 11px; color: var(--danger); max-width: 180px; overflow: hidden; text-overflow: ellipsis;">Reason: ${w.verification_rejection_reason}</div>` : ''}
          </td>
          <td>
            <div style="display: flex; gap: 6px;">
              ${isPending || isRejected ? `
                <button class="btn btn-success btn-sm btn-approve-verif" data-id="${w.id}" data-name="${u.name || 'Worker'}">
                  Approve
                </button>
              ` : ''}
              ${isPending || isVerified ? `
                <button class="btn btn-danger btn-sm btn-reject-verif" data-id="${w.id}" data-name="${u.name || 'Worker'}">
                  Reject
                </button>
              ` : ''}
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
              <th>Worker</th>
              <th>Documents</th>
              <th>Status</th>
              <th>Submitted Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${items.length}</strong> of <strong>${paginated.total || items.length}</strong> verifications</div>
        <div class="pagination-controls">
          <button id="verif-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="verif-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    container.querySelectorAll('.btn-approve-verif').forEach(btn => {
      btn.addEventListener('click', () => confirmApprove(btn.dataset.id, btn.dataset.name));
    });

    container.querySelectorAll('.btn-reject-verif').forEach(btn => {
      btn.addEventListener('click', () => promptReject(btn.dataset.id, btn.dataset.name));
    });

    const prevBtn = document.getElementById('verif-prev-page');
    const nextBtn = document.getElementById('verif-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadVerifications(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadVerifications(); });

  } catch (err) {
    Toast.error('Failed to load verifications: ' + err.message);
  }
}

function confirmApprove(id, workerName) {
  Modal.open({
    title: `Approve Verification — ${workerName}`,
    contentHtml: `
      <div style="font-size: 13px;">
        <p>Are you sure you want to approve worker verification for <strong>${workerName} (Profile #${id})</strong>?</p>
        <p style="color: var(--text-muted); margin-top: 8px;">The worker will be marked as verified, granted the verified badge, and notified automatically.</p>
      </div>
    `,
    footerButtons: [
      { label: 'Cancel', onClick: (_, m) => m.close() },
      {
        label: 'Confirm Approval',
        className: 'btn-success',
        onClick: async (_, m) => {
          try {
            await Api.approveVerification(id);
            Toast.success(`Worker ${workerName} has been approved and verified!`);
            m.close();
            loadVerifications();
          } catch (err) {
            Toast.error('Approval failed: ' + err.message);
          }
        }
      }
    ]
  });
}

function promptReject(id, workerName) {
  Modal.open({
    title: `Reject Verification — ${workerName}`,
    contentHtml: `
      <div style="font-size: 13px;">
        <p style="margin-bottom: 12px;">Please specify the reason for rejecting verification for <strong>${workerName}</strong>:</p>
        <div class="form-group">
          <label class="form-label" for="reject-reason">Rejection Reason *</label>
          <textarea id="reject-reason" class="input-textarea" rows="3" placeholder="e.g. Identity document unreadable or expired..." required></textarea>
        </div>
      </div>
    `,
    footerButtons: [
      { label: 'Cancel', onClick: (_, m) => m.close() },
      {
        label: 'Confirm Rejection',
        className: 'btn-danger',
        onClick: async (_, m) => {
          const reasonInput = document.getElementById('reject-reason');
          const reason = reasonInput ? reasonInput.value.trim() : '';
          if (!reason) {
            Toast.error('Please enter a rejection reason.');
            return;
          }

          try {
            await Api.rejectVerification(id, reason);
            Toast.success(`Verification rejected for ${workerName}.`);
            m.close();
            loadVerifications();
          } catch (err) {
            Toast.error('Rejection failed: ' + err.message);
          }
        }
      }
    ]
  });
}

export function attachVerificationsEvents() {
  const searchInput = document.getElementById('verif-search');
  const statusFilter = document.getElementById('verif-status-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadVerifications();
      }, 300);
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      state.status = e.target.value;
      state.page = 1;
      loadVerifications();
    });
  }

  loadVerifications();
}
