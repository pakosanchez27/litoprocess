// Reusable notification wrapper using SweetAlert2 with fallbacks
// Exposes `window.Notify` with methods: missingSteps, alert, confirm, toast
(function () {
  // Try to import SweetAlert2 if available in the bundle scope
  let SwalLib = (typeof Swal !== 'undefined' ? Swal : null);

  // If running in bundler environment where imports work, try dynamic import
  try {
    // eslint-disable-next-line no-undef
    if (!SwalLib && typeof require === 'function') {
      try {
        SwalLib = require('sweetalert2');
      } catch (e) {
        // ignore
      }
    }
  } catch (e) {
    // ignore
  }

  function fallbackAlert(title, text) {
    window.alert((title ? title + '\n\n' : '') + (text || ''));
    return Promise.resolve();
  }

  const Notify = {
    missingSteps() {
      if (SwalLib && typeof SwalLib.fire === 'function') {
        return SwalLib.fire({
          icon: 'warning',
          title: 'Faltan pasos',
          text: 'Agrega al menos un paso en el desarrollo del procedimiento antes de generar el diagrama.',
          confirmButtonText: 'Entendido'
        });
      }
      // SweetAlert v1 fallback
      if (typeof swal === 'function') {
        try { return Promise.resolve(swal('Faltan pasos', 'Agrega al menos un paso en el desarrollo del procedimiento antes de generar el diagrama.', 'warning')); } catch (e) { /* fall through */ }
      }
      return fallbackAlert('Faltan pasos', 'Agrega al menos un paso en el desarrollo del procedimiento antes de generar el diagrama.');
    },

    alert(opts) {
      if (SwalLib && typeof SwalLib.fire === 'function') {
        return SwalLib.fire(opts || {});
      }
      const title = (opts && (opts.title || opts.text)) ? (opts.title || '') : '';
      const text = (opts && opts.text) ? opts.text : '';
      return fallbackAlert(title, text);
    },

    confirm(opts) {
      if (SwalLib && typeof SwalLib.fire === 'function') {
        const conf = Object.assign({
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Sí',
          cancelButtonText: 'Cancelar'
        }, opts || {});
        return SwalLib.fire(conf).then(r => r.isConfirmed);
      }
      // Fallback confirm()
      const ok = window.confirm((opts && opts.title ? opts.title + '\n\n' : '') + (opts && opts.text ? opts.text : '')); 
      return Promise.resolve(!!ok);
    },

    toast(opts) {
      if (SwalLib && typeof SwalLib.fire === 'function') {
        return SwalLib.fire(Object.assign({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000
        }, opts || {}));
      }
      // fallback to alert (non-ideal)
      return fallbackAlert(opts && opts.title ? opts.title : '', opts && opts.text ? opts.text : '');
    }
  };

  // Expose globally
  window.Notify = Notify;

  // Global factory to create reusable modals
  // Returns an object: { show(), close(), update(newOpts) }
  window.createModal = function createModal(initialOpts = {}) {
    let opts = Object.assign({}, initialOpts);
    let bsInstance = null;
    let domEl = null;
    let swalOpen = false;

    // Helper to create a Bootstrap modal DOM if bootstrap is available
    function createBootstrapModal() {
      const id = 'modal-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
      const wrapper = document.createElement('div');
      wrapper.innerHTML = `
        <div class="modal fade" id="${id}" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog ${opts.size || ''}">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">${opts.title || ''}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">${opts.html || opts.text || ''}</div>
              ${opts.footer ? `<div class="modal-footer">${opts.footer}</div>` : ''}
            </div>
          </div>
        </div>`;
      document.body.appendChild(wrapper);
      const el = wrapper.querySelector('.modal');
      return { wrapper, el };
    }

    // Minimal DOM fallback modal
    function createDOMModal() {
      const overlay = document.createElement('div');
      overlay.style.position = 'fixed';
      overlay.style.left = 0;
      overlay.style.top = 0;
      overlay.style.right = 0;
      overlay.style.bottom = 0;
      overlay.style.background = 'rgba(0,0,0,0.5)';
      overlay.style.display = 'flex';
      overlay.style.alignItems = 'center';
      overlay.style.justifyContent = 'center';
      overlay.style.zIndex = 2000;

      const box = document.createElement('div');
      box.style.background = '#fff';
      box.style.borderRadius = '8px';
      box.style.padding = '16px';
      box.style.maxWidth = '90%';
      box.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)';
      box.innerHTML = `
        <div style="font-weight:600;margin-bottom:8px">${opts.title || ''}</div>
        <div style="margin-bottom:12px">${opts.html || opts.text || ''}</div>
        <div style="text-align:right"><button class="modal-ok">${(opts.confirmText||'OK')}</button></div>
      `;
      overlay.appendChild(box);
      document.body.appendChild(overlay);

      const btn = box.querySelector('.modal-ok');
      const close = () => { overlay.remove(); };
      btn.addEventListener('click', close);

      return { wrapper: overlay, el: box };
    }

    return {
      async show() {
        // Prefer Swal
        if (SwalLib && typeof SwalLib.fire === 'function') {
          swalOpen = true;
          const result = await SwalLib.fire(opts);
          swalOpen = false;
          return result;
        }

        // Then Bootstrap modal
        if (typeof window.bootstrap !== 'undefined' && window.bootstrap && window.bootstrap.Modal) {
          const created = createBootstrapModal();
          domEl = created.wrapper;
          const el = created.el;
          bsInstance = new window.bootstrap.Modal(el, opts.bootstrap || {});
          bsInstance.show();
          // Return a promise that resolves when hidden
          return new Promise((resolve) => {
            el.addEventListener('hidden.bs.modal', function handler() {
              el.removeEventListener('hidden.bs.modal', handler);
              // cleanup
              if (domEl && domEl.parentNode) domEl.parentNode.removeChild(domEl);
              resolve();
            });
          });
        }

        // Fallback DOM modal
        const created = createDOMModal();
        domEl = created.wrapper;
        return Promise.resolve();
      },

      close() {
        if (swalOpen && SwalLib && typeof SwalLib.close === 'function') {
          try { SwalLib.close(); } catch (e) { /* ignore */ }
        }
        if (bsInstance) {
          try { bsInstance.hide(); } catch (e) { /* ignore */ }
          if (domEl && domEl.parentNode) domEl.parentNode.removeChild(domEl);
          bsInstance = null;
          domEl = null;
        }
        if (domEl && domEl.parentNode) {
          domEl.parentNode.removeChild(domEl);
          domEl = null;
        }
      },

      update(newOpts) {
        opts = Object.assign({}, opts, newOpts || {});
        // If bootstrap DOM is present, update title/body/footer
        if (domEl) {
          const titleEl = domEl.querySelector('.modal-title');
          const bodyEl = domEl.querySelector('.modal-body');
          const footerEl = domEl.querySelector('.modal-footer');
          if (titleEl) titleEl.innerHTML = opts.title || '';
          if (bodyEl) bodyEl.innerHTML = opts.html || opts.text || '';
          if (footerEl && opts.footer) footerEl.innerHTML = opts.footer;
        }
      }
    };
  };
})();
