import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  search: '',
  page: 1,
};

export function renderConversationsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">💬 Platform Conversations & Chat Moderation</h2>
        <div class="panel-actions">
          <input type="text" id="chats-search" class="input-search" placeholder="Search customer, worker, job..." value="${state.search}" />
        </div>
      </div>

      <div id="chats-table-container">
        <div class="loading-state">Loading conversations...</div>
      </div>
    </div>
  `;
}

async function loadConversations() {
  const container = document.getElementById('chats-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading conversations...</div>';

  try {
    const res = await Api.getConversations({
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const chats = paginated.data || [];

    if (chats.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">💬</div>
          <div>No conversations found.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = chats.map(c => {
      const cust = c.customer || {};
      const w = c.worker || {};
      const job = c.job || {};

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${c.id}</strong></td>
          <td>
            <div style="font-weight: 600;">${job.title || 'Job #' + c.job_id}</div>
            <div style="font-size: 11px; color: var(--text-muted);">${job.status || 'Active'}</div>
          </td>
          <td>${cust.name || 'Customer'}</td>
          <td>${w.name || 'Worker'}</td>
          <td><span class="badge badge-info">${c.messages_count || 0} messages</span></td>
          <td>${c.last_message_at ? new Date(c.last_message_at).toLocaleString() : new Date(c.updated_at).toLocaleString()}</td>
          <td>
            <button class="btn btn-secondary btn-sm btn-inspect-chat" data-id="${c.id}">
              Inspect Messages
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
              <th>Related Job</th>
              <th>Customer</th>
              <th>Worker</th>
              <th>Messages</th>
              <th>Last Message</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>

      <div class="pagination">
        <div>Showing <strong>${chats.length}</strong> of <strong>${paginated.total || chats.length}</strong> conversations</div>
        <div class="pagination-controls">
          <button id="chats-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="chats-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    container.querySelectorAll('.btn-inspect-chat').forEach(btn => {
      btn.addEventListener('click', () => inspectConversation(btn.dataset.id));
    });

    const prevBtn = document.getElementById('chats-prev-page');
    const nextBtn = document.getElementById('chats-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadConversations(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadConversations(); });

  } catch (err) {
    Toast.error('Failed to load conversations: ' + err.message);
  }
}

async function inspectConversation(id) {
  try {
    const res = await Api.getConversation(id);
    const conv = res.data.conversation || {};
    const messages = res.data.messages?.data || [];

    const messagesHtml = messages.length === 0
      ? '<div style="color: var(--text-muted); font-size: 13px; text-align: center; padding: 20px;">No messages sent yet.</div>'
      : messages.map(m => {
          const isCustomer = m.sender_id === conv.customer_id;
          const senderName = isCustomer ? (conv.customer?.name || 'Customer') : (conv.worker?.name || 'Worker');

          return `
            <div style="margin-bottom: 12px; display: flex; flex-direction: column; align-items: ${isCustomer ? 'flex-start' : 'flex-end'};">
              <div style="font-size: 11px; color: var(--text-subtle); margin-bottom: 2px;">
                <strong>${senderName}</strong> • ${new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
              </div>
              <div style="background: ${isCustomer ? 'var(--bg-input)' : 'rgba(99, 102, 241, 0.2)'}; border: 1px solid var(--border-subtle); padding: 10px 14px; border-radius: var(--radius-md); max-width: 80%; font-size: 13px;">
                ${m.message || ''}
              </div>
            </div>
          `;
        }).join('');

    Modal.open({
      title: `Chat Moderation — Job: ${conv.job?.title || '#' + conv.job_id}`,
      contentHtml: `
        <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid var(--border-subtle);">
          Customer: <strong>${conv.customer?.name || '—'}</strong> | Worker: <strong>${conv.worker?.name || '—'}</strong>
          <div style="margin-top: 4px; color: var(--warning);">Note: Administrator access is read-only for moderation purposes.</div>
        </div>
        <div style="max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; padding-right: 6px;">
          ${messagesHtml}
        </div>
      `,
      footerButtons: [
        { label: 'Close', onClick: (_, m) => m.close() }
      ]
    });
  } catch (err) {
    Toast.error('Failed to load messages: ' + err.message);
  }
}

export function attachConversationsEvents() {
  const searchInput = document.getElementById('chats-search');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadConversations();
      }, 300);
    });
  }

  loadConversations();
}
