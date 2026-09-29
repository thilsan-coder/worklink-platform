// Reusable Modal Component
export const Modal = {
  open({ title, contentHtml, footerButtons = [] }) {
    this.close();

    const root = document.getElementById('modal-root') || document.body;
    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop';
    backdrop.id = 'active-modal';

    const buttonsHtml = footerButtons.map((btn, idx) => `
      <button id="modal-btn-${idx}" class="btn ${btn.className || 'btn-secondary'}">
        ${btn.label}
      </button>
    `).join('');

    backdrop.innerHTML = `
      <div class="modal-dialog">
        <div class="modal-header">
          <h3 class="modal-title">${title}</h3>
          <button class="modal-close" id="modal-close-btn">&times;</button>
        </div>
        <div class="modal-body">
          ${contentHtml}
        </div>
        ${footerButtons.length ? `<div class="modal-footer">${buttonsHtml}</div>` : ''}
      </div>
    `;

    root.appendChild(backdrop);

    // Event handlers
    backdrop.querySelector('#modal-close-btn').addEventListener('click', () => this.close());
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) this.close();
    });

    footerButtons.forEach((btn, idx) => {
      const btnEl = backdrop.querySelector(`#modal-btn-${idx}`);
      if (btnEl && btn.onClick) {
        btnEl.addEventListener('click', (e) => btn.onClick(e, this));
      }
    });

    return backdrop;
  },

  close() {
    const existing = document.getElementById('active-modal');
    if (existing) {
      existing.remove();
    }
  },
};
