import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  status: '',
  search: '',
  page: 1,
};

export function renderPaymentsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">💳 Payment Records & Gateways</h2>
        <div class="panel-actions">
          <input type="text" id="payments-search" class="input-search" placeholder="Search reference, user..." value="${state.search}" />
          <select id="payments-status-filter" class="select-filter">
            <option value="" ${state.status === '' ? 'selected' : ''}>All Statuses</option>
            <option value="PAID" ${state.status === 'PAID' ? 'selected' : ''}>PAID</option>
            <option value="PENDING" ${state.status === 'PENDING' ? 'selected' : ''}>PENDING</option>
            <option value="PROCESSING" ${state.status === 'PROCESSING' ? 'selected' : ''}>PROCESSING</option>
            <option value="FAILED" ${state.status === 'FAILED' ? 'selected' : ''}>FAILED</option>
            <option value="REFUNDED" ${state.status === 'REFUNDED' ? 'selected' : ''}>REFUNDED</option>
          </select>
        </div>
      </div>

      <div id="payments-table-container">
        <div class="loading-state">Loading payments...</div>
      </div>
    </div>
  `;
}

async function loadPayments() {
  const container = document.getElementById('payments-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading payments...</div>';

  try {
    const res = await Api.getPayments({
      status: state.status,
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const payments = paginated.data || [];

    if (payments.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">💳</div>
          <div>No payment records found.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = payments.map(p => {
      const cust = p.customer || {};
      const w = p.worker || {};
      const job = p.job || {};

      let badge = `<span class="badge badge-secondary">${p.status}</span>`;
      if (p.status === 'PAID') badge = `<span class="badge badge-success">✓ PAID</span>`;
      else if (p.status === 'PENDING' || p.status === 'PROCESSING') badge = `<span class="badge badge-warning">⏳ ${p.status}</span>`;
      else if (p.status === 'FAILED') badge = `<span class="badge badge-danger">✗ FAILED</span>`;
      else if (p.status === 'REFUNDED') badge = `<span class="badge badge-info">↩ REFUNDED</span>`;

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${p.id}</strong></td>
          <td>
            <div style="font-weight: 600;">${job.title || 'Job #' + p.job_id}</div>
            <div style="font-size: 11px; color: var(--text-subtle);">${p.gateway_transaction_id || '—'}</div>
          </td>
          <td>${cust.name || 'Customer'}</td>
          <td>${w.name || 'Worker'}</td>
          <td><strong style="color: var(--text-main);">LKR ${Number(p.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong></td>
          <td><span class="badge badge-secondary">${p.gateway || 'test'} / ${p.payment_method || 'card'}</span></td>
          <td>${badge}</td>
          <td>${new Date(p.created_at).toLocaleDateString()}</td>
          <td>
            <button class="btn btn-secondary btn-sm btn-view-payment" data-id="${p.id}">
              Receipt
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
              <th>Job / Ref</th>
              <th>Customer</th>
              <th>Worker</th>
              <th>Amount</th>
              <th>Gateway</th>
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
        <div>Showing <strong>${payments.length}</strong> of <strong>${paginated.total || payments.length}</strong> payments</div>
        <div class="pagination-controls">
          <button id="payments-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="payments-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    container.querySelectorAll('.btn-view-payment').forEach(btn => {
      btn.addEventListener('click', () => viewPaymentDetails(btn.dataset.id));
    });

    const prevBtn = document.getElementById('payments-prev-page');
    const nextBtn = document.getElementById('payments-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadPayments(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadPayments(); });

  } catch (err) {
    Toast.error('Failed to load payments: ' + err.message);
  }
}

async function viewPaymentDetails(id) {
  try {
    const res = await Api.getPayment(id);
    const p = res.data;

    const txnsHtml = p.transactions?.map(t => `
      <div style="padding: 8px 12px; background: rgba(255,255,255,0.03); border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; font-size: 12px;">
        <div>
          <div><strong>${t.reference}</strong> (${t.type})</div>
          <div style="font-size: 11px; color: var(--text-muted);">${new Date(t.created_at).toLocaleString()}</div>
        </div>
        <div style="text-align: right;">
          <div><strong>LKR ${Number(t.amount).toLocaleString('en-US', { minimumFractionDigits: 2 })}</strong></div>
          <span class="badge ${t.status === 'SUCCESS' ? 'badge-success' : 'badge-danger'}">${t.status}</span>
        </div>
      </div>
    `).join('') || '<div style="color: var(--text-muted); font-size: 12px;">No transactions recorded.</div>';

    Modal.open({
      title: `Payment Receipt #${p.id}`,
      contentHtml: `
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; font-size: 13px;">
            <div><strong>Amount:</strong> LKR ${Number(p.amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</div>
            <div><strong>Status:</strong> ${p.status}</div>
            <div><strong>Customer:</strong> ${p.customer?.name || '—'}</div>
            <div><strong>Worker:</strong> ${p.worker?.name || '—'}</div>
            <div><strong>Gateway:</strong> ${p.gateway || 'test'} (${p.payment_method || 'card'})</div>
            <div><strong>Gateway Ref:</strong> ${p.gateway_transaction_id || '—'}</div>
            <div><strong>Created:</strong> ${new Date(p.created_at).toLocaleString()}</div>
            <div><strong>Paid At:</strong> ${p.paid_at ? new Date(p.paid_at).toLocaleString() : '—'}</div>
            ${p.failure_reason ? `<div style="grid-column: span 2; color: var(--danger);"><strong>Failure Reason:</strong> ${p.failure_reason}</div>` : ''}
          </div>

          <div style="border-top: 1px solid var(--border-subtle); padding-top: 14px;">
            <h4 style="margin-bottom: 8px; font-size: 13px; color: var(--text-muted);">📑 Linked Ledger Transactions</h4>
            <div style="display: flex; flex-direction: column; gap: 8px;">
              ${txnsHtml}
            </div>
          </div>
        </div>
      `,
      footerButtons: [
        { label: 'Close', onClick: (_, m) => m.close() }
      ]
    });
  } catch (err) {
    Toast.error('Failed to load payment details: ' + err.message);
  }
}

export function attachPaymentsEvents() {
  const searchInput = document.getElementById('payments-search');
  const statusFilter = document.getElementById('payments-status-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadPayments();
      }, 300);
    });
  }

  if (statusFilter) {
    statusFilter.addEventListener('change', (e) => {
      state.status = e.target.value;
      state.page = 1;
      loadPayments();
    });
  }

  loadPayments();
}
