import { Api } from '../api.js';
import { Toast } from '../components/Toast.js';
import { Modal } from '../components/Modal.js';

let state = {
  rating: '',
  search: '',
  page: 1,
};

export function renderReviewsView() {
  return `
    <div class="panel">
      <div class="panel-header">
        <h2 class="panel-title">⭐ Review & Rating Moderation</h2>
        <div class="panel-actions">
          <input type="text" id="reviews-search" class="input-search" placeholder="Search comments or users..." value="${state.search}" />
          <select id="reviews-rating-filter" class="select-filter">
            <option value="" ${state.rating === '' ? 'selected' : ''}>All Ratings</option>
            <option value="5" ${state.rating === '5' ? 'selected' : ''}>5 Stars ⭐⭐⭐⭐⭐</option>
            <option value="4" ${state.rating === '4' ? 'selected' : ''}>4 Stars ⭐⭐⭐⭐</option>
            <option value="3" ${state.rating === '3' ? 'selected' : ''}>3 Stars ⭐⭐⭐</option>
            <option value="2" ${state.rating === '2' ? 'selected' : ''}>2 Stars ⭐⭐</option>
            <option value="1" ${state.rating === '1' ? 'selected' : ''}>1 Star ⭐</option>
          </select>
        </div>
      </div>

      <div id="reviews-table-container">
        <div class="loading-state">Loading reviews...</div>
      </div>
    </div>
  `;
}

async function loadReviews() {
  const container = document.getElementById('reviews-table-container');
  if (!container) return;

  container.innerHTML = '<div class="loading-state">Loading reviews...</div>';

  try {
    const res = await Api.getReviews({
      rating: state.rating,
      search: state.search,
      page: state.page,
    });

    const paginated = res.data || {};
    const reviews = paginated.data || [];

    if (reviews.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <div class="empty-icon">⭐</div>
          <div>No reviews found.</div>
        </div>
      `;
      return;
    }

    const rowsHtml = reviews.map(r => {
      const cust = r.customer || {};
      const w = r.worker || {};
      const job = r.job || {};

      const stars = '⭐'.repeat(Math.round(r.overall_rating || 5));

      return `
        <tr>
          <td><strong style="color: var(--text-muted);">#${r.id}</strong></td>
          <td>
            <div style="font-size: 14px; color: #FBBF24; font-weight: 700;">${stars} (${r.overall_rating})</div>
          </td>
          <td>
            <div style="max-width: 280px; font-size: 13px; color: var(--text-main); line-height: 1.4;">
              "${r.comment || 'No comment text'}"
            </div>
          </td>
          <td>${cust.name || 'Customer'}</td>
          <td>${w.name || 'Worker'}</td>
          <td>
            <div style="font-size: 12px; font-weight: 600;">${job.title || 'Job #' + r.job_id}</div>
          </td>
          <td>${new Date(r.created_at).toLocaleDateString()}</td>
          <td>
            <button class="btn btn-danger btn-sm btn-delete-review" data-id="${r.id}">
              Moderate / Delete
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
              <th>Rating</th>
              <th>Comment</th>
              <th>Customer</th>
              <th>Worker</th>
              <th>Job</th>
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
        <div>Showing <strong>${reviews.length}</strong> of <strong>${paginated.total || reviews.length}</strong> reviews</div>
        <div class="pagination-controls">
          <button id="reviews-prev-page" class="btn btn-secondary btn-sm" ${paginated.current_page <= 1 ? 'disabled' : ''}>Previous</button>
          <span style="display: flex; align-items: center; padding: 0 10px; font-size: 12px;">Page ${paginated.current_page || 1} of ${paginated.last_page || 1}</span>
          <button id="reviews-next-page" class="btn btn-secondary btn-sm" ${paginated.current_page >= paginated.last_page ? 'disabled' : ''}>Next</button>
        </div>
      </div>
    `;

    container.querySelectorAll('.btn-delete-review').forEach(btn => {
      btn.addEventListener('click', () => confirmDeleteReview(btn.dataset.id));
    });

    const prevBtn = document.getElementById('reviews-prev-page');
    const nextBtn = document.getElementById('reviews-next-page');
    if (prevBtn) prevBtn.addEventListener('click', () => { state.page--; loadReviews(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { state.page++; loadReviews(); });

  } catch (err) {
    Toast.error('Failed to load reviews: ' + err.message);
  }
}

function confirmDeleteReview(id) {
  Modal.open({
    title: `Moderate Review #${id}`,
    contentHtml: `
      <div style="font-size: 13px;">
        <p style="margin-bottom: 10px;">Are you sure you want to remove and moderate <strong>Review #${id}</strong>?</p>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: var(--radius-md); padding: 10px; color: #F87171;">
          ⚠️ This action will remove the review and automatically recalculate the worker's average rating and total review counts in real time.
        </div>
      </div>
    `,
    footerButtons: [
      { label: 'Cancel', onClick: (_, m) => m.close() },
      {
        label: 'Confirm Delete',
        className: 'btn-danger',
        onClick: async (_, m) => {
          try {
            await Api.deleteReview(id);
            Toast.success(`Review #${id} moderated. Worker rating updated.`);
            m.close();
            loadReviews();
          } catch (err) {
            Toast.error('Failed to delete review: ' + err.message);
          }
        }
      }
    ]
  });
}

export function attachReviewsEvents() {
  const searchInput = document.getElementById('reviews-search');
  const ratingFilter = document.getElementById('reviews-rating-filter');

  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        state.search = e.target.value.trim();
        state.page = 1;
        loadReviews();
      }, 300);
    });
  }

  if (ratingFilter) {
    ratingFilter.addEventListener('change', (e) => {
      state.rating = e.target.value;
      state.page = 1;
      loadReviews();
    });
  }

  loadReviews();
}
