import { Auth } from '../auth.js';

export function renderSidebar(activeRoute = 'dashboard', pendingVerificationsCount = 0) {
  const admin = Auth.getUser() || { name: 'Admin', role: 'super_admin' };
  const initials = (admin.name || 'Admin')
    .split(' ')
    .map(n => n[0])
    .join('')
    .substring(0, 2)
    .toUpperCase();

  const links = [
    { section: 'Overview' },
    { route: 'dashboard', label: 'Dashboard', icon: '📊' },

    { section: 'User Management' },
    { route: 'users', label: 'Users', icon: '👥' },
    { route: 'workers', label: 'Workers', icon: '🔧' },
    { route: 'verifications', label: 'Verifications', icon: '🛡️', badge: pendingVerificationsCount > 0 ? pendingVerificationsCount : null },

    { section: 'Operations' },
    { route: 'jobs', label: 'Jobs', icon: '📋' },
    { route: 'conversations', label: 'Conversations', icon: '💬' },
    { route: 'reviews', label: 'Reviews', icon: '⭐' },

    { section: 'Finance' },
    { route: 'payments', label: 'Payments', icon: '💳' },
    { route: 'transactions', label: 'Transactions', icon: '📑' },

    { section: 'System & Security' },
    { route: 'notifications', label: 'Notifications', icon: '🔔' },
    { route: 'audit-logs', label: 'Audit Logs', icon: '📜' },
  ];

  const linksHtml = links.map(item => {
    if (item.section) {
      return `<div class="sidebar-section-title">${item.section}</div>`;
    }
    const isActive = activeRoute === item.route ? 'active' : '';
    const badgeHtml = item.badge ? `<span class="nav-badge">${item.badge}</span>` : '';
    return `
      <a href="#${item.route}" class="nav-link ${isActive}" data-route="${item.route}">
        <span class="icon">${item.icon}</span>
        <span>${item.label}</span>
        ${badgeHtml}
      </a>
    `;
  }).join('');

  return `
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="sidebar-logo-icon">W</div>
        <div class="sidebar-brand">Work<span>Link</span> Admin</div>
      </div>
      <nav class="sidebar-nav">
        ${linksHtml}
      </nav>
      <div class="sidebar-footer">
        <div class="admin-profile-card">
          <div class="admin-avatar">${initials}</div>
          <div class="admin-info">
            <div class="admin-name">${admin.name || 'System Admin'}</div>
            <div class="admin-role">${admin.role || 'Admin'}</div>
          </div>
        </div>
      </div>
    </aside>
  `;
}
