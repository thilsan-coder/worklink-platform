import { Api } from '../api.js';
import { Auth } from '../auth.js';
import { Toast } from '../components/Toast.js';

export function renderLoginView() {
  return `
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <div class="login-logo">W</div>
          <h2 class="login-title">WorkLink Admin</h2>
          <p class="login-subtitle">Sign in to platform management dashboard</p>
        </div>

        <form id="admin-login-form" class="login-form">
          <div class="form-group">
            <label class="form-label" for="login-username">Username or Email</label>
            <input type="text" id="login-username" class="input-text" placeholder="admin or admin@worklink.com" required value="admin" />
          </div>

          <div class="form-group">
            <label class="form-label" for="login-password">Password</label>
            <input type="password" id="login-password" class="input-text" placeholder="••••••••••••" required value="AdminPass123!" />
          </div>

          <button type="submit" id="btn-submit-login" class="btn btn-primary" style="padding: 12px; margin-top: 8px;">
            Sign In to Dashboard
          </button>
        </form>

        <div class="demo-credentials-box">
          <div style="font-weight: 700; margin-bottom: 4px; color: var(--text-main);">Demo Admin Account:</div>
          <div>Username: <strong>admin</strong></div>
          <div>Password: <strong>AdminPass123!</strong></div>
          <button id="btn-fill-demo" class="btn btn-secondary btn-sm" style="margin-top: 8px; width: 100%;">
            Auto-fill Demo Credentials
          </button>
        </div>
      </div>
    </div>
  `;
}

export function attachLoginEvents() {
  const form = document.getElementById('admin-login-form');
  const btnFill = document.getElementById('btn-fill-demo');

  if (btnFill) {
    btnFill.addEventListener('click', () => {
      document.getElementById('login-username').value = 'admin';
      document.getElementById('login-password').value = 'AdminPass123!';
    });
  }

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const submitBtn = document.getElementById('btn-submit-login');
      const usernameOrEmail = document.getElementById('login-username').value.trim();
      const password = document.getElementById('login-password').value;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Authenticating...';

      try {
        const response = await Api.login(usernameOrEmail, password);
        if (response.success && response.data.token) {
          Auth.setToken(response.data.token);
          Auth.setUser(response.data.admin);
          Toast.success('Welcome back, ' + (response.data.admin.name || 'Admin'));
          window.location.hash = '#dashboard';
        } else {
          Toast.error(response.message || 'Login failed.');
        }
      } catch (err) {
        Toast.error(err.message || 'Invalid credentials or server error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign In to Dashboard';
      }
    });
  }
}
