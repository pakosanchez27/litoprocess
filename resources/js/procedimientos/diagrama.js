
// =================== Utilidades ===================
const $ = (id) => document.getElementById(id);
const debounce = (fn, ms = 350) => {
  let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
};

// =================== Init + Render principal ===================
// window.addEventListener('DOMContentLoaded', () => {
//   if (!window.mermaid) {
//     console.warn('Mermaid no está cargado en la página.');
//     return;
//   }

//   // 1) Inicializa Mermaid
//   window.mermaid.initialize({
//     startOnLoad: false,     // renderizamos manualmente
//     securityLevel: 'loose', // permite enlaces y estilos en SVG
//     theme: 'dark'           // opcional
//   });

//   const codeEl = $('mermaidCode');
//   const previewEl = $('mermaidPreview');
//   const errEl = $('mermaidError');

//   // 2) Renderizador único y expuesto
//   window.renderMermaid = async function renderMermaid() {
//     if (!codeEl || !previewEl) return;
//     const code = (codeEl.value || '').trim();

//     // Estado inicial
//     if (errEl) errEl.style.display = 'none';
//     previewEl.textContent = 'Renderizando…';

//     try {
//       // Valida sintaxis (lanza excepción si hay error)
//       await window.mermaid.parse(code);

//       // Render a SVG
//       const { svg } = await window.mermaid.render('mmd-' + Date.now(), code);
//       previewEl.innerHTML = svg;

//       // Toma el SVG y guarda en hidden
//       const svgEl = previewEl.querySelector('svg');
//       if (!svgEl) throw new Error('No se generó SVG');

//       // Serializa a string y convierte a base64 seguro
//       const svgText = new XMLSerializer().serializeToString(svgEl);
//       const svgBase64 = btoa(unescape(encodeURIComponent(svgText)));

//       // Guarda en inputs ocultos si existen
//       const svgHidden = $('diagrama_svg');
//       const codeHidden = $('diagrama_code');
//       if (svgHidden) svgHidden.value = svgBase64; // almacén base64
//       if (codeHidden) codeHidden.value = code;

//     } catch (e) {
//       previewEl.textContent = '—';
//       if (errEl) {
//         errEl.textContent = (e?.str || e?.message || String(e));
//         errEl.style.display = 'block';
//       }
//       console.error('Error Mermaid:', e);
//     }
//   };

//   // 3) Botón (si existe) para render manual
//   $('btnRenderMermaid')?.addEventListener('click', window.renderMermaid);

//   // 4) Pre-cargar ejemplo simple desde botón "Reset con pasos" (si existe)
//   $('btnFromSteps')?.addEventListener('click', () => {
//     if (!codeEl) return;
//     codeEl.value =
//       `flowchart TD
//     A[Inicio] --> B{¿Valida?}
//     B -- Sí --> C[Procesar]
//     B -- No --> D[Revisar]
//     C --> E[Fin]
//     D --> B`;
//     if (errEl) errEl.style.display = 'none';
//     if (previewEl) previewEl.textContent = 'Renderizando…';
//     window.renderMermaid();
//   });

//   // 5) Auto-render al escribir (debounce)
//   if (codeEl) {
//     const debouncedRender = debounce(() => window.renderMermaid(), 400);
//     codeEl.addEventListener('input', debouncedRender);
//     codeEl.addEventListener('change', debouncedRender);
//   }

//   // 6) Render inicial si hay código prellenado
//   if (codeEl && codeEl.value.trim()) {
//     window.renderMermaid();
//   }
// });

// =================== Fullscreen del preview ===================
(function () {
  const previewEl = $('mermaidPreview');
  const fsBtn = $('btnFullscreen');
  if (!previewEl || !fsBtn) return;

  async function enterFS() {
    try {
      await (previewEl.requestFullscreen?.() || previewEl.webkitRequestFullscreen?.());
    } catch (e) {
      console.error('No se pudo entrar a fullscreen:', e);
    }
  }
  async function exitFS() {
    try {
      await (document.exitFullscreen?.() || document.webkitExitFullscreen?.());
    } catch (e) {
      console.error('No se pudo salir de fullscreen:', e);
    }
  }
  function syncButton() {
    const inFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
    fsBtn.textContent = inFS ? '⤢ Salir de pantalla completa' : '⛶ Pantalla completa';
  }
  fsBtn.addEventListener('click', () => {
    const inFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
    inFS ? exitFS() : enterFS();
  });
  // Doble-click sobre el área de previsualización para alternar
  previewEl.addEventListener('dblclick', () => {
    const inFS = !!(document.fullscreenElement || document.webkitFullscreenElement);
    inFS ? exitFS() : enterFS();
  });
  document.addEventListener('fullscreenchange', syncButton);
  document.addEventListener('webkitfullscreenchange', syncButton);
  syncButton();
})();

// =================== Generar texto desde pasos (IA local) ===================
function diagramaIA() {
  // readDesarrollo() debe existir en tu proyecto y devolver array de pasos: [{ actividad: "..." }, ...]
  const pasos = (typeof readDesarrollo === 'function' ? (readDesarrollo() || []) : []);
  const actividades = pasos
    .map(p => (p && typeof p.actividad === 'string' ? p.actividad.trim() : ''))
    .filter(Boolean);

  // Aquí solo regresamos un texto compacto con las actividades (tu backend lo transformará a Mermaid).
  const texto = actividades.map((act, i) => `Paso ${i + 1}: ${act}`).join(', ');
  return texto;
}

// =================== Generar código via backend + RENDER AUTO ===================
async function renderDiagramaIA() {
  const previewEl = $('mermaidPreview');
  const errorEl = $('mermaidError');
  const codeEl = $('mermaidCode');
  const btn = $('btnGenerarCodigo');

  // Use global Notify helper (provided by resources/js/utils/notify.js)

  // 🌀 Estado inicial (spinner y bloqueo de botón)
  if (errorEl) errorEl.style.display = 'none';
  if (previewEl) {
    previewEl.classList.add('is-loading');
    previewEl.innerHTML = `
      <div class="mmd-spinner" role="status" aria-live="polite" aria-label="Generando diagrama…"></div>
      <div class="mmd-loading-text">Generando diagrama…</div>
    `;
  }
  if (btn) { btn.classList.add('is-disabled'); btn.setAttribute('disabled', ''); }

  try {
    // Validación: leer los pasos actuales y bloquear si está vacío
    const pasosActuales = (typeof readDesarrollo === 'function') ? (readDesarrollo() || []) : [];
    if (!Array.isArray(pasosActuales) || pasosActuales.length === 0) {
      // Restaurar estado visual si se puso spinner
      if (previewEl) previewEl.classList.remove('is-loading');
      if (btn) {
        btn.classList.remove('is-disabled');
        btn.removeAttribute('disabled');
      }
      // Mostrar modal reutilizable: preferir createModal -> Notify.missingSteps -> alert
      if (typeof window.createModal === 'function') {
        const modal = window.createModal({
          title: 'Faltan pasos',
          text: 'Agrega al menos un paso en el desarrollo del procedimiento antes de generar el diagrama.',
          confirmText: 'Agregar paso',
        });

        await modal.show();

        // Si existe el botón para ir a agregar pasos, enfocarlo
        const goBtn = document.getElementById('modal-go-add');
        if (goBtn) {
          goBtn.addEventListener('click', () => {
            // cerrar modal y enfocar el area de pasos
            modal.close();
            const addBtn = document.getElementById('btnAgregarPaso');
            if (addBtn) {
              addBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
              addBtn.focus();
            }
          });
        }

      } else if (window.Notify && typeof window.Notify.missingSteps === 'function') {
        await window.Notify.missingSteps();
      } else {
        // Fallback si Notify no está disponible
        await (async () => alert('Faltan pasos. Agrega al menos un paso en el desarrollo del procedimiento antes de generar el diagrama.'))();
      }
      return; // salir sin generar
    }
    // 🧩 === NUEVO BLOQUE: sincronizar pasos antes de generar el diagrama ===
    const form = document.getElementById('procedimientoForm') || document.querySelector('form');
    const desarrollo = (typeof readDesarrollo === 'function') ? readDesarrollo() : [];
    let desarrolloInput = document.getElementById('desarrollo_json');

    if (form) {
      if (!desarrolloInput) {
        desarrolloInput = document.createElement('input');
        desarrolloInput.type = 'hidden';
        desarrolloInput.name = 'desarrollo';
        desarrolloInput.id = 'desarrollo_json';
        form.appendChild(desarrolloInput);
      }

      desarrolloInput.value = JSON.stringify(desarrollo);
      console.log('🧩 Pasos sincronizados antes de generar diagrama:', desarrollo);
    } else {
      console.warn('No se encontró el formulario para sincronizar pasos. Pasos:', desarrollo);
    }

    // 1️⃣ Obtener pasos desde tu generador local (IA u otra fuente)
    const pasos = await Promise.resolve(diagramaIA());

    // 2️⃣ Enviar los pasos a tu backend local (Laravel) para convertirlos en código Mermaid
    const res = await fetch('/ia/diagrama', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
      },
      body: JSON.stringify({ pasos })
    });

    // 3️⃣ Obtener respuesta con código Mermaid o fallback
    let data = {};
    try { data = await res.json(); } catch (_) { data = {}; }
    const mermaidCode = data?.response || 'flowchart TD; A["Error al generar diagrama"];';
    if (codeEl) codeEl.value = mermaidCode;

    // 4️⃣ Enviar el código Mermaid al webhook de n8n (genera el PNG)
    const response = await fetch('https://litoprocess.aeo-ai.com/webhook/generar-diagrama', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ pasos: mermaidCode })
    });

    if (!response.ok) throw new Error(`Error en webhook: ${response.status}`);

    // 5️⃣ Recibir imagen (PNG binario)
    const blob = await response.blob();
    const imageUrl = URL.createObjectURL(blob);

    // 6️⃣ Mostrar el PNG en el contenedor de vista previa
    if (previewEl) {
      previewEl.classList.remove('is-loading');
      previewEl.innerHTML = ''; // limpiar
      const img = document.createElement('img');
      img.src = imageUrl;
      img.alt = 'Diagrama Mermaid generado';
      img.style.maxWidth = '100%';
      img.style.borderRadius = '8px';
      img.style.background = '#fff';
      img.style.boxShadow = '0 0 8px rgba(0,0,0,0.1)';
      previewEl.appendChild(img);
    }

  } catch (e) {
    // ⚠️ Manejo de errores visual y consola
    if (previewEl) {
      previewEl.classList.remove('is-loading');
      previewEl.textContent = '—';
    }
    if (errorEl) {
      errorEl.textContent = e?.message || String(e);
      errorEl.style.display = 'block';
    }
    console.error('renderDiagramaIA error:', e);

  } finally {
    // 🔚 Restaurar estado del botón y limpiar spinner
    if (previewEl) previewEl.classList.remove('is-loading');
    if (btn) {
      btn.classList.remove('is-disabled');
      btn.removeAttribute('disabled');
    }
  }
}

// Protege el acceso a btnGenerarPDF (puede no existir en la vista)
(function () {
  const btn = document.getElementById('btnGenerarPDF');
  if (!btn) return;

  btn.addEventListener('click', async function (e) {
    const form = this.closest('form');
    const previewEl = document.getElementById('mermaidPreview');
    const hiddenInput = document.getElementById('diagrama_png');

    // Verifica si hay una imagen ya generada en el preview
    const img = previewEl?.querySelector('img');
    if (!img) {
      // Si no hay diagrama renderizado, no lo impedimos pero avisamos
      console.warn('⚠️ No hay diagrama generado aún, se enviará sin imagen.');
      return; // deja que el form se envíe normal
    }

    e.preventDefault(); // detenemos temporalmente el submit

    try {
      // 1️⃣ Convertir el blob/imagen visible a Base64
      const imageUrl = img.src;
      const blob = await fetch(imageUrl).then(r => r.blob());

      const base64 = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onloadend = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });

      // 2️⃣ Insertar la imagen codificada en un input oculto
      if (hiddenInput) hiddenInput.value = base64;

      // 3️⃣ Enviar el formulario normalmente
      if (form) form.submit();

    } catch (err) {
      console.error('Error al adjuntar el diagrama al formulario:', err);
      alert('Hubo un problema al adjuntar el diagrama. Intenta de nuevo.');
    }
  });
})();



window.renderDiagramaIA = renderDiagramaIA;