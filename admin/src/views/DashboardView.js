import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';

export async function renderDashboardView() {
  return `
    <div id="dashboard-content">
      <div class="loading-state">Loading dashboard analytics...</div>
    </div>
  `;
}

export async function attachDashboardEvents() {
  const container = document.getElementById('dashboard-content');
  if (!container) return;

  try {
    const [statsRes, activityRes] = await Promise.all([
      Api.getStats(),
      Api.getRecentActivity(),
    ]);

    const s = statsRes.data || {};
    const activities = activityRes.data || [];

    const formatLkr = (num) => 'LKR ' + Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2 });

    container.innerHTML = `
      <!-- Pending Verification Alert Banner -->
      ${s.pending_verifications > 0 ? `
        <div style="background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-lg); padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 24px;">🛡️</span>
            <div>
              <div style="font-weight: 700; color: var(--warning);">Verification Actions Needed</div>
              <div style="font-size: 13px; color: var(--text-muted);">${s.pending_verifications} worker profile(s) awaiting administrative verification.</div>
            </div>
          </div>
          <a href="#verifications" class="btn btn-primary btn-sm">Review Now ➔</a>
        </div>
      ` : ''}

      <!-- Top Metric Cards Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-title">Total Users</span>
            <div class="stat-icon-wrapper" style="background: rgba(99, 102, 241, 0.15); color: var(--primary);">👥</div>
          </div>
          <div class="stat-value">${(s.total_users || 0).toLocaleString()}</div>
          <div class="stat-subtext">
            <span>${s.total_customers || 0} Customers</span> • <span>${s.total_workers || 0} Workers</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-title">Verified Workers</span>
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">✓</div>
          </div>
          <div class="stat-value">${(s.verified_workers || 0).toLocaleString()}</div>
          <div class="stat-subtext">
            <span style="color: var(--warning);">${s.pending_verifications || 0} Pending</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-title">Total Jobs</span>
            <div class="stat-icon-wrapper" style="background: rgba(6, 182, 212, 0.15); color: var(--secondary);">📋</div>
          </div>
          <div class="stat-value">${(s.total_jobs || 0).toLocaleString()}</div>
          <div class="stat-subtext">
            <span>${s.active_jobs || 0} Active</span> • <span style="color: var(--success);">${s.completed_jobs || 0} Completed</span>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <span class="stat-title">Total Payments</span>
            <div class="stat-icon-wrapper" style="background: rgba(16, 185, 129, 0.15); color: var(--success);">💳</div>
          </div>
          <div class="stat-value" style="font-size: 20px;">${formatLkr(s.total_transaction_amount)}</div>
          <div class="stat-subtext">
            <span>${s.successful_payments || 0} Paid</span> • <span style="color: var(--danger);">${s.failed_payments || 0} Failed</span>
          </div>
        </div>
      </div>

      <!-- Financial & Platform Summary Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 28px;">
        <!-- Financial Card -->
        <div class="panel" style="margin-bottom: 0;">
          <div class="panel-header">
            <h3 class="panel-title">💰 Financial Performance</h3>
            <a href="#payments" class="btn btn-secondary btn-sm">View Ledger</a>
          </div>
          <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
              <span style="color: var(--text-muted); font-size: 13px;">Total Successful Payments:</span>
              <strong style="color: var(--success); font-size: 15px;">${formatLkr(s.total_transaction_amount)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
              <span style="color: var(--text-muted); font-size: 13px;">Total Refunded:</span>
              <strong style="color: var(--warning); font-size: 15px;">${formatLkr(s.total_refunded_amount)}</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="color: var(--text-muted); font-size: 13px;">Payment Success Rate:</span>
              <strong style="color: var(--text-main); font-size: 15px;">
                ${s.total_payments ? Math.round((s.successful_payments / s.total_payments) * 100) : 100}%
              </strong>
            </div>
          </div>
        </div>

        <!-- Platform Health Card -->
        <div class="panel" style="margin-bottom: 0;">
          <div class="panel-header">
            <h3 class="panel-title">⭐ Platform Ratings & Categories</h3>
            <a href="#reviews" class="btn btn-secondary btn-sm">Reviews</a>
          </div>
          <div style="padding: 20px; display: flex; flex-direction: column; gap: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
              <span style="color: var(--text-muted); font-size: 13px;">Overall Average Rating:</span>
              <strong style="color: #FBBF24; font-size: 15px;">⭐ ${s.average_rating || '5.0'} / 5.0 (${s.total_reviews || 0} reviews)</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-subtle); padding-bottom: 12px;">
              <span style="color: var(--text-muted); font-size: 13px;">Active Categories & Skills:</span>
              <strong style="color: var(--text-main); font-size: 15px;">${s.total_categories || 0} Categories • ${s.total_skills || 0} Skills</strong>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="color: var(--text-muted); font-size: 13px;">Open Complaints:</span>
              <strong style="color: ${s.open_complaints > 0 ? 'var(--danger)' : 'var(--success)'}; font-size: 15px;">
                ${s.open_complaints || 0} Open
              </strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Platform Activity Feed -->
      <div class="panel">
        <div class="panel-header">
          <h3 class="panel-title">⚡ Live Recent Activity</h3>
          <span style="font-size: 12px; color: var(--text-subtle);">Real-time database events</span>
        </div>
        <div class="activity-list">
          ${activities.length === 0 ? `
            <div class="empty-state">
              <div class="empty-icon">📭</div>
              <div>No recent activity records found.</div>
            </div>
          ` : activities.map(act => {
            let icon = '📌';
            let iconBg = 'rgba(255, 255, 255, 0.08)';
            if (act.type.includes('USER')) { icon = '👤'; iconBg = 'rgba(99, 102, 241, 0.15)'; }
            else if (act.type.includes('JOB')) { icon = '📋'; iconBg = 'rgba(6, 182, 212, 0.15)'; }
            else if (act.type.includes('PAYMENT')) { icon = '💳'; iconBg = 'rgba(16, 185, 129, 0.15)'; }
            else if (act.type.includes('REVIEW')) { icon = '⭐'; iconBg = 'rgba(245, 158, 11, 0.15)'; }

            const dateFormatted = new Date(act.created_at).toLocaleString();

            return `
              <div class="activity-item">
                <div class="activity-icon" style="background: ${iconBg};">${icon}</div>
                <div class="activity-content">
                  <div class="activity-title">${act.title}</div>
                  <div class="activity-desc">${act.description}</div>
                  <div class="activity-time">${dateFormatted}</div>
                </div>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  } catch (err) {
    Toast.error('Failed to load dashboard data: ' + err.message);
    container.innerHTML = `<div class="empty-state" style="color: var(--danger);">Failed to load stats: ${err.message}</div>`;
  }
}
