import { test, describe, beforeEach } from 'node:test';
import assert from 'node:assert/strict';

// Mock localStorage and sessionStorage for Node.js environment
const createStorageMock = () => {
  let store = {};
  return {
    getItem: (k) => store[k] || null,
    setItem: (k, v) => { store[k] = String(v); },
    removeItem: (k) => { delete store[k]; },
    clear: () => { store = {}; },
  };
};

globalThis.localStorage = createStorageMock();
globalThis.sessionStorage = createStorageMock();

// Dynamic imports
const { Auth } = await import('../src/auth.js');
const { renderSidebar } = await import('../src/components/Sidebar.js');
const { renderNavbar } = await import('../src/components/Navbar.js');
const { renderLoginView } = await import('../src/views/LoginView.js');
const { renderUsersView } = await import('../src/views/UsersView.js');
const { renderWorkersView } = await import('../src/views/WorkersView.js');
const { renderVerificationsView } = await import('../src/views/VerificationsView.js');
const { renderJobsView } = await import('../src/views/JobsView.js');
const { renderConversationsView } = await import('../src/views/ConversationsView.js');
const { renderReviewsView } = await import('../src/views/ReviewsView.js');
const { renderPaymentsView } = await import('../src/views/PaymentsView.js');
const { renderTransactionsView } = await import('../src/views/TransactionsView.js');
const { renderNotificationsView } = await import('../src/views/NotificationsView.js');
const { renderAuditLogsView } = await import('../src/views/AuditLogsView.js');

describe('Admin Authentication & Session State Tests', () => {
  beforeEach(() => {
    Auth.clear();
  });

  test('Auth.isAuthenticated returns false initially', () => {
    assert.equal(Auth.isAuthenticated(), false);
    assert.equal(Auth.getToken(), null);
    assert.equal(Auth.getUser(), null);
  });

  test('Auth.setToken and getToken store and retrieve token correctly', () => {
    Auth.setToken('test-admin-bearer-token-12345');
    assert.equal(Auth.isAuthenticated(), true);
    assert.equal(Auth.getToken(), 'test-admin-bearer-token-12345');
  });

  test('Auth.setUser and getUser serialize and deserialize admin user', () => {
    const adminUser = { id: 1, name: 'System Administrator', role: 'super_admin', email: 'admin@worklink.com' };
    Auth.setUser(adminUser);
    const retrieved = Auth.getUser();
    assert.deepEqual(retrieved, adminUser);
  });

  test('Auth.clear wipes all credentials and returns to unauthenticated state', () => {
    Auth.setToken('dummy-token');
    Auth.setUser({ id: 1, name: 'Admin' });
    assert.equal(Auth.isAuthenticated(), true);

    Auth.clear();
    assert.equal(Auth.isAuthenticated(), false);
    assert.equal(Auth.getToken(), null);
    assert.equal(Auth.getUser(), null);
  });
});

describe('Admin UI Components & Layout Tests', () => {
  beforeEach(() => {
    Auth.setUser({ name: 'System Admin', role: 'super_admin' });
  });

  test('renderSidebar renders all 11 core admin navigation sections and links', () => {
    const html = renderSidebar('dashboard', 5);
    assert.match(html, /Dashboard/);
    assert.match(html, /Users/);
    assert.match(html, /Workers/);
    assert.match(html, /Verifications/);
    assert.match(html, /Jobs/);
    assert.match(html, /Conversations/);
    assert.match(html, /Reviews/);
    assert.match(html, /Payments/);
    assert.match(html, /Transactions/);
    assert.match(html, /Notifications/);
    assert.match(html, /Audit Logs/);
    assert.match(html, /nav-badge/); // Check pending verifications badge
  });

  test('renderNavbar renders page title and current admin information', () => {
    const html = renderNavbar('User Management');
    assert.match(html, /User Management/);
    assert.match(html, /System Admin/);
    assert.match(html, /btn-topbar-logout/);
  });
});

describe('Admin Views HTML Structure Tests', () => {
  test('LoginView renders administrative login form with demo hints', () => {
    const html = renderLoginView();
    assert.match(html, /WorkLink Admin/);
    assert.match(html, /login-username/);
    assert.match(html, /login-password/);
    assert.match(html, /btn-submit-login/);
    assert.match(html, /Demo Admin Account/);
  });

  test('UsersView renders filter controls and table container', () => {
    const html = renderUsersView();
    assert.match(html, /users-search/);
    assert.match(html, /users-role-filter/);
    assert.match(html, /users-status-filter/);
    assert.match(html, /users-table-container/);
  });

  test('WorkersView renders search and rating filters', () => {
    const html = renderWorkersView();
    assert.match(html, /workers-search/);
    assert.match(html, /workers-verif-filter/);
    assert.match(html, /workers-rating-filter/);
  });

  test('VerificationsView renders verification hub with pending status selector', () => {
    const html = renderVerificationsView();
    assert.match(html, /verif-status-filter/);
    assert.match(html, /verif-table-container/);
  });

  test('JobsView renders job lifecycle status filter', () => {
    const html = renderJobsView();
    assert.match(html, /jobs-status-filter/);
    assert.match(html, /jobs-table-container/);
  });

  test('ConversationsView renders chat moderation container', () => {
    const html = renderConversationsView();
    assert.match(html, /chats-search/);
    assert.match(html, /chats-table-container/);
  });

  test('ReviewsView renders 5-star rating filter', () => {
    const html = renderReviewsView();
    assert.match(html, /reviews-rating-filter/);
    assert.match(html, /reviews-table-container/);
  });

  test('PaymentsView and TransactionsView render financial filters', () => {
    const p增Html = renderPaymentsView();
    assert.match(p增Html, /payments-status-filter/);
    assert.match(p增Html, /payments-table-container/);

    const tHtml = renderTransactionsView();
    assert.match(tHtml, /txn-type-filter/);
    assert.match(tHtml, /txn-status-filter/);
  });

  test('NotificationsView and AuditLogsView render system monitoring containers', () => {
    const nHtml = renderNotificationsView();
    assert.match(nHtml, /notif-read-filter/);

    const aHtml = renderAuditLogsView();
    assert.match(aHtml, /audit-action-filter/);
  });
});
