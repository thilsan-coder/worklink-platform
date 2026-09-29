import { Auth } from '../auth.js';
import { Api } from '../api.js';
import { Toast } from './Toast.js';

export function renderNavbar(title = 'Dashboard') {
  const admin = Auth.getUser() || { name: 'Admin' };

  return `
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">${title}</h1>
      </div>
      <div class="topbar-right">
        <span style="font-size: 13px; color: var(--text-muted);">Signed in as <strong style="color: var(--text-main);">${admin.name || 'Admin'}</strong></span>
        <button id="btn-topbar-logout" class="btn-logout" title="Sign out">
          <span>Logout</span> ➔
        </button>
      </div>
    </header>
  `;
}

export function attachNavbarEvents() {
  const logoutBtn = document.getElementById('btn-topbar-logout');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try {
        await Api.logout();
      } catch (e) {
        console.warn('Logout error (continuing local logout):', e);
      }
      Auth.clear();
      Toast.info('Logged out successfully');
      window.location.hash = '#login';
    });
  }
}
