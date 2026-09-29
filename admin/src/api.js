import { Auth } from './auth.js';

// Base API URL dynamic resolution
const getBaseUrl = () => {
  // If running on localhost with default ports
  if (typeof window !== 'undefined' && window.location.port === '5173') {
    return 'http://localhost:8000/api';
  }
  return '/api';
};

const API_BASE = getBaseUrl();

async function request(endpoint, options = {}) {
  const url = `${API_BASE}${endpoint}`;
  const token = Auth.getToken();

  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
    ...(options.headers || {}),
  };

  try {
    const response = await fetch(url, {
      ...options,
      headers,
    });

    if (response.status === 401 && !endpoint.includes('/admin/login')) {
      Auth.clear();
      window.location.hash = '#login';
      throw new Error('Session expired. Please log in again.');
    }

    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || `Request failed with status ${response.status}`);
    }

    return data;
  } catch (error) {
    console.error(`API Error [${endpoint}]:`, error);
    throw error;
  }
}

export const Api = {
  // Auth
  login(username_or_email, password) {
    return request('/admin/login', {
      method: 'POST',
      body: JSON.stringify({ username_or_email, password }),
    });
  },

  logout() {
    return request('/admin/logout', { method: 'POST' });
  },

  getProfile() {
    return request('/admin/profile');
  },

  // Dashboard & Stats
  getStats() {
    return request('/admin/stats');
  },

  getRecentActivity() {
    return request('/admin/recent-activity');
  },

  // Users
  getUsers(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/users${query ? `?${query}` : ''}`);
  },

  getUser(id) {
    return request(`/admin/users/${id}`);
  },

  updateUserStatus(id, status, reason = '') {
    return request(`/admin/users/${id}/status`, {
      method: 'PATCH',
      body: JSON.stringify({ status, reason }),
    });
  },

  // Workers
  getWorkers(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/workers${query ? `?${query}` : ''}`);
  },

  getWorker(id) {
    return request(`/admin/workers/${id}`);
  },

  // Verification
  getVerifications(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/verifications${query ? `?${query}` : ''}`);
  },

  getVerification(id) {
    return request(`/admin/verifications/${id}`);
  },

  approveVerification(id) {
    return request(`/admin/verifications/${id}/approve`, { method: 'POST' });
  },

  rejectVerification(id, reason) {
    return request(`/admin/verifications/${id}/reject`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    });
  },

  // Jobs
  getJobs(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/jobs${query ? `?${query}` : ''}`);
  },

  getJob(id) {
    return request(`/admin/jobs/${id}`);
  },

  // Conversations
  getConversations(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/conversations${query ? `?${query}` : ''}`);
  },

  getConversation(id) {
    return request(`/admin/conversations/${id}`);
  },

  // Reviews
  getReviews(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/reviews${query ? `?${query}` : ''}`);
  },

  getReview(id) {
    return request(`/admin/reviews/${id}`);
  },

  deleteReview(id) {
    return request(`/admin/reviews/${id}`, { method: 'DELETE' });
  },

  // Payments & Transactions
  getPayments(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/payments${query ? `?${query}` : ''}`);
  },

  getPayment(id) {
    return request(`/admin/payments/${id}`);
  },

  getTransactions(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/transactions${query ? `?${query}` : ''}`);
  },

  getTransaction(id) {
    return request(`/admin/transactions/${id}`);
  },

  // Notifications & Audit Logs
  getNotifications(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/notifications${query ? `?${query}` : ''}`);
  },

  getAuditLogs(params = {}) {
    const query = new URLSearchParams(params).toString();
    return request(`/admin/audit-logs${query ? `?${query}` : ''}`);
  },
};
