import { Auth } from './auth.js';
import { renderSidebar } from './components/Sidebar.js';
import { renderNavbar, attachNavbarEvents } from './components/Navbar.js';
import { renderLoginView, attachLoginEvents } from './views/LoginView.js';
import { renderDashboardView, attachDashboardEvents } from './views/DashboardView.js';
import { renderUsersView, attachUsersEvents } from './views/UsersView.js';
import { renderWorkersView, attachWorkersEvents } from './views/WorkersView.js';
import { renderVerificationsView, attachVerificationsEvents } from './views/VerificationsView.js';
import { renderJobsView, attachJobsEvents } from './views/JobsView.js';
import { renderConversationsView, attachConversationsEvents } from './views/ConversationsView.js';
import { renderReviewsView, attachReviewsEvents } from './views/ReviewsView.js';
import { renderPaymentsView, attachPaymentsEvents } from './views/PaymentsView.js';
import { renderTransactionsView, attachTransactionsEvents } from './views/TransactionsView.js';
import { renderNotificationsView, attachNotificationsEvents } from './views/NotificationsView.js';
import { renderAuditLogsView, attachAuditLogsEvents } from './views/AuditLogsView.js';

const routes = {
  login: { title: 'Admin Login', render: renderLoginView, attach: attachLoginEvents, isPublic: true },
  dashboard: { title: 'Dashboard Overview', render: renderDashboardView, attach: attachDashboardEvents },
  users: { title: 'User Management', render: renderUsersView, attach: attachUsersEvents },
  workers: { title: 'Worker Directory', render: renderWorkersView, attach: attachWorkersEvents },
  verifications: { title: 'Worker Verification Hub', render: renderVerificationsView, attach: attachVerificationsEvents },
  jobs: { title: 'Job Operations', render: renderJobsView, attach: attachJobsEvents },
  conversations: { title: 'Conversation Moderation', render: renderConversationsView, attach: attachConversationsEvents },
  reviews: { title: 'Reviews & Ratings', render: renderReviewsView, attach: attachReviewsEvents },
  payments: { title: 'Payments & Gateway Records', render: renderPaymentsView, attach: attachPaymentsEvents },
  transactions: { title: 'Financial Ledger', render: renderTransactionsView, attach: attachTransactionsEvents },
  notifications: { title: 'Platform Notifications', render: renderNotificationsView, attach: attachNotificationsEvents },
  'audit-logs': { title: 'Administrative Audit Logs', render: renderAuditLogsView, attach: attachAuditLogsEvents },
};

export async function navigate() {
  const hash = window.location.hash.replace(/^#/, '').trim() || 'dashboard';
  const routeKey = hash.split('?')[0];

  const targetRoute = routes[routeKey] || routes.dashboard;

  // Authentication Guard
  if (!targetRoute.isPublic && !Auth.isAuthenticated()) {
    window.location.hash = '#login';
    return;
  }

  if (targetRoute.isPublic && Auth.isAuthenticated() && routeKey === 'login') {
    window.location.hash = '#dashboard';
    return;
  }

  const appEl = document.getElementById('app');
  if (!appEl) return;

  if (targetRoute.isPublic) {
    appEl.innerHTML = targetRoute.render();
    if (targetRoute.attach) targetRoute.attach();
    return;
  }

  // Render Authenticated Admin Shell
  appEl.innerHTML = `
    <div class="admin-layout">
      ${renderSidebar(routeKey)}
      <div class="main-wrapper">
        ${renderNavbar(targetRoute.title)}
        <main class="content-body" id="view-mount-point">
          ${await targetRoute.render()}
        </main>
      </div>
    </div>
  `;

  attachNavbarEvents();
  if (targetRoute.attach) {
    await targetRoute.attach();
  }
}

export function initRouter() {
  window.addEventListener('hashchange', () => navigate());
  navigate();
}
