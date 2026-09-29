import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';

let state = {
  type: '',
  status: '',
  search: '',
  page: 1,
};

export function renderTransactionsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">📑 Financial Ledger & Transactions</h2>
        <div class="panel-actions">
          <input type="text" id="txn-search" class="input-search" placeholder="Search reference or user..." value="${state.search}" />
          <select id="txn-type-filter" class="select-filter">
            <option value="" ${state.type === '' ? 'selected' : ''}>All Types</option>
            <option value="PAYMENT" ${state.type === 'PAYMENT' ? 'selected' : ''}>PAYMENT</option>
            <option value="REFUND" ${state.type === 'REFUND' ? 'selected' : ''}>REFUND</option>
          </select>
          <select id="txn-status-filter" class="select-filter">
            <option value="" ${state.status === '' ? 'selected' : ''}>All Statuses</option>
            <option value="SUCCESS" ${state.status === 'SUCCESS' ? 'selected' : ''}>SUCCESS</option>
            <option value="FAILED" ${state.status === 'FAILED' ? 'selected' : ''}>FAILED</option>
          </select>
        </div>
      </div>

      <div id="txn-table-container">
        <div class="loading-state">Loading ledger transactions...</div>
      </div>
    </div>
  `;
}

async function loadTransactions() {
  const container = document.getElementById('txn-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading ledger transactions...</div>';

  try {
    const res = await Api.getTransactions({
      type: state.type,
      status: state.status,
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const txns = paginated.data || [];

    if (txns.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">📑</div>
          <div>No transaction ledger records found.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = txns.map(t => {
      const cust = t.customer || {};
      const w = t.worker || {};
      const job = t.job || {};

      const typeBadge = t.type === 'PAYMENT'
        ? '<span class="badge badge-success">PAYMENT</span>'
        : '<span class="badge badge-info">REFUND</span>';

      const statusBadge = t.status === 'SUCCESS'
        ? '<span class="badge badge-success">✓ SUCCESS</span>'
        : '<span class="badge badge-danger">✗ FAILED</span>';

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${t.id}</strong></td>
          <td><code style="font-size: 12px; color: var(--primary);">${t.reference}</code></td>
          <td>${typeBadge}</td>
          <td><strong>LKR ${Number(t.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong></td>
          <td>${cust.name || 'Customer'}</td>
          <td>${w.name || 'Worker'}</td>
          <td>${job.title || 'Job #' + t.job_id}</td>
          <td>${statusBadge}</td>
          <td>${new Date(t.created_at).toLocaleString()}</td>
        </tr>
      `;
    }).join('');

    container.innerHTML = `
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Reference</th>
              <th>Type</th>
              <th>Amount</th>
              <th>Customer</th>
              <th>Worker</th>
              <th>Job</th>
              <th>Status</th>
              <th>Recorded Date</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${txns.length}</strong> of <strong>${paginated.total || txns.length}</strong> transactions</div>
        <div class="pagination-controls">
          <button id="txn-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="txn-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    const prevBtn = document.getElementById('txn-prev-page');
    const nextBtn = document.getElementById('txn-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadTransactions(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadTransactions(); });

  } catch (err) {
    Toast.error('Failed to load transactions: ' + err.message);
  }
}

export function attachTransactionsEvents() {
  const searchInput = document.getElementById('txn-search');
  const typeFilter = document.getElementById('txn-type-filter');
  const statusFilter = document.getElementById('txn-status-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadTransactions();
      }, 300);
    });
  }

  if (typeFilter) {
    typeFilter.addEventListener('change', (e) => {
      state.type = e.target.value;
      state.page = 1;
      loadTransactions();
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      state.status = e.target.value;
      state.page = 1;
      loadTransactions();
    });
  }

  loadTransactions();
}
