/* ========= Metadatos: autogenera código ========= */
document.addEventListener('DOMContentLoaded', () => {
    const tipo = document.getElementById('tipo_documento');
    const area = document.getElementById('area_codigo');
    const consecutivo = document.getElementById('consecutivo');
    const fsc = document.getElementById('es_fsc');
    const codigo = document.getElementById('codigo');

    function generarCodigo() {
        const tipoVal = tipo.value || '';
        const areaVal = area.value || '';
        const consecutivoVal = (consecutivo.value || '').padStart(3, '0');
        const fscVal = fsc.checked ? 'FSC' : '';

        if (tipoVal && areaVal && consecutivoVal) {
            const partes = ['L', tipoVal];
            if (fscVal) partes.push(fscVal);
            partes.push(areaVal, consecutivoVal);
            codigo.value = partes.join('-');
        } else {
            codigo.value = '';
        }
    }

    [tipo, area, consecutivo, fsc].forEach(el => {
        el.addEventListener('input', generarCodigo);
        el.addEventListener('change', generarCodigo);
    });

    // Inicial
    generarCodigo();
});

/* ========= Chips de Referencias Normativas ========= */
document.addEventListener("DOMContentLoaded", function () {
    const input = document.getElementById("ref_tag_input");
    const box = document.getElementById("ref_tag_box");
    const hidden = document.getElementById("referencias_normativas");

    let tags = [];

    function renderTags() {
        box.querySelectorAll(".chip").forEach(el => el.remove());

        tags.forEach((tag, i) => {
            const chip = document.createElement("span");
            chip.className = "chip badge bg-primary d-flex align-items-center";
            chip.style.gap = "6px";
            chip.style.padding = "6px 10px";
            chip.innerHTML = `
        <span>${tag}</span>
        <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Eliminar" data-index="${i}"></button>
      `;
            chip.querySelector("button").addEventListener("click", () => {
                tags.splice(i, 1);
                renderTags();
            });
            box.insertBefore(chip, input);
        });

        hidden.value = tags.join(",");
    }

    input.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === "," || e.key === "Tab") {
            e.preventDefault();
            const val = this.value.trim();
            if (val && !tags.includes(val)) {
                tags.push(val);
                renderTags();
            }
            this.value = "";
        } else if (e.key === "Backspace" && this.value === "") {
            tags.pop();
            renderTags();
        }
    });

    // Restaura valores (old)
    if (hidden.value) {
        tags = hidden.value.split(",").map(t => t.trim()).filter(Boolean);
        renderTags();
    }
});


/* ========= Definiciones ========= */
document.addEventListener("DOMContentLoaded", function () {
    const terminoInput = document.getElementById("termino");
    const definicionInput = document.getElementById("definicion");
    const addBtn = document.getElementById("addDefinicion");
    const list = document.getElementById("definiciones-list");
    const hidden = document.getElementById("definiciones_json");

    let definiciones = [];

    if (hidden.value) {
        try {
            definiciones = JSON.parse(hidden.value);
        } catch {
            definiciones = [];
        }
    }

    function renderDefiniciones() {
        list.innerHTML = "";
        definiciones.forEach((d, index) => {
            const li = document.createElement("li");
            li.className = "list-group-item d-flex justify-content-between align-items-center";
            li.innerHTML = `
        <div><strong>${d.termino}</strong>: ${d.definicion}</div>
        <button type="button" class="btn btn-sm btn-danger" title="Eliminar">✖</button>
      `;
            li.querySelector("button").addEventListener("click", () => {
                definiciones.splice(index, 1);
                renderDefiniciones();
            });
            list.appendChild(li);
        });
        hidden.value = JSON.stringify(definiciones);
    }

    addBtn.addEventListener("click", () => {
        const termino = terminoInput.value.trim();
        const definicion = definicionInput.value.trim();
        if (!termino || !definicion) return;
        definiciones.push({
            termino,
            definicion
        });
        terminoInput.value = "";
        definicionInput.value = "";
        renderDefiniciones();
    });

    renderDefiniciones();
});

/* ========= Pasos (drag & drop + IA botón) ========= */
(function () {
    'use strict';

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    const refs = {
        form: $('#procedimientoForm'),
        pasosContainer: $('#pasosContainer'),
        btnAgregarPaso: $('#btnAgregarPaso'),
    };

    let stepGlobalCount = 0;

    function agregarPaso(prefill = {
        responsable: '',
        actividad: ''
    }) {
        const actividadId = `act-${stepGlobalCount++}`;
        const paso = document.createElement('div');
        paso.className = 'col-12 paso';
        paso.setAttribute('draggable', 'true');
        paso.innerHTML = `
      <div class="card border shadow-sm p-3 mb-2">
        <div class="row g-3 align-items-start">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Responsable</label>
            <input type="text" name="responsable" class="form-control"
                   placeholder="Nombre o puesto responsable"
                   value="${(prefill.responsable || '').replace(/"/g, '&quot;')}">
          </div>

          <div class="col-md-7">
            <label class="form-label fw-semibold">Actividad</label>
            <textarea id="${actividadId}" name="actividad" class="form-control" rows="3"
                      placeholder="Describa la actividad...">${(prefill.actividad || '')
                .replace(/</g, '&lt;').replace(/>/g, '&gt;')}</textarea>

            <div class="d-flex justify-content-between align-items-center mt-2">
              <button type="button" class="btn btn-outline-primary btn-sm"
                      data-bs-toggle="tooltip" data-bs-placement="top"
                      title="<i class='bx bx-pencil-sparkles bx-xs'></i> <span>Mejorar redacción</span>"
                      onclick="redactarActividadIA('${actividadId}')">
                <i class="bx bx-pencil-sparkles"></i> Mejorar redacción
              </button>
              <small class="text-muted"><i class="bx bx-move-vertical"></i> Arrastra para reordenar</small>
            </div>
          </div>

          <div class="col-md-1 d-flex justify-content-end align-items-start">
            <button class="btn btn-outline-danger btn-sm" type="button" data-role="del-step"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar paso">
              <i class="bx bx-trash"></i>
            </button>
          </div>
        </div>
      </div>
    `;

        makeDraggable(paso, refs.pasosContainer);
        refs.pasosContainer.appendChild(paso);
        checkEmpty(refs.pasosContainer);
    }

    function readDesarrollo() {
        return $$('.paso', refs.pasosContainer).map(pasoEl => {
            const responsable = pasoEl.querySelector('input[name="responsable"]')?.value.trim() || '';
            const actividad = pasoEl.querySelector('textarea[name="actividad"]')?.value.trim() || '';
            if (!responsable && !actividad) return null;
            return {
                responsable,
                actividad
            };
        }).filter(Boolean);
    }

    function checkEmpty(container) {
        if (!container.querySelector('.paso')) {
            if (!container.querySelector('.empty')) {
                const empty = document.createElement('div');
                empty.className = 'empty text-muted small fst-italic text-center p-2';
                empty.textContent = 'Sin pasos. Agrega al menos uno.';
                container.appendChild(empty);
            }
        } else {
            container.querySelector('.empty')?.remove();
        }
    }

    function makeDraggable(el, container) {
        el.addEventListener('dragstart', e => {
            el.classList.add('ghost');
            e.dataTransfer.setData('text/plain', 'x');
        });
        el.addEventListener('dragend', () => el.classList.remove('ghost'));
        container.addEventListener('dragover', e => {
            e.preventDefault();
            const after = getDragAfterElement(container, e.clientY);
            if (after == null) container.appendChild(el);
            else container.insertBefore(el, after);
        });
    }

    function getDragAfterElement(container, y) {
        const els = [...container.querySelectorAll('.paso:not(.ghost)')];
        return els.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return {
                    offset,
                    element: child
                };
            }
            return closest;
        }, {
            offset: Number.NEGATIVE_INFINITY
        }).element;
    }

    refs.pasosContainer?.addEventListener('click', (e) => {
        const roleBtn = e.target.closest('[data-role]');
        if (roleBtn?.dataset.role === 'del-step') {
            roleBtn.closest('.paso')?.remove();
            checkEmpty(refs.pasosContainer);
        }
    });

    refs.btnAgregarPaso?.addEventListener('click', () => agregarPaso());

    // Restaurar pasos desde old()
    (function restorePasos() {
        const hidden = document.getElementById('desarrollo_json');
        if (!hidden || !hidden.value) return;
        try {
            const data = JSON.parse(hidden.value);
            (data || []).forEach(p => agregarPaso(p));
        } catch { }
        checkEmpty(refs.pasosContainer);
    })();

    // Expone para otros scripts si lo deseas
    window.agregarPaso = agregarPaso;
    window.readDesarrollo = readDesarrollo;
})();

/* ========= Políticas ========= */
(function () {
    'use strict';
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

    const refs = {
        container: $('#politicasContainer'),
        addBtn: $('#btnAgregarPolitica'),
        hidden: $('#politicas_json'),
    };

    let politicaCount = 0;

    function agregarPolitica(prefill = '') {
        const id = `politica-${politicaCount++}`;
        const div = document.createElement('div');
        div.className = 'card mb-2 border shadow-sm p-3 politica-item';
        div.innerHTML = `
      <div class="d-flex flex-column gap-2">
        <div>
          <label for="${id}" class="form-label fw-semibold">Política</label>
          <textarea id="${id}" name="politica_${politicaCount}" class="form-control" rows="3"
                    placeholder="Describe una política...">${prefill}</textarea>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-1">
          <button type="button" class="btn btn-outline-primary btn-sm"
                  data-bs-toggle="tooltip" data-bs-placement="top"
                  title="<i class='bx bx-pencil-sparkles bx-xs'></i> <span>Mejorar redacción</span>"
                  onclick="redactarPoliticasIA('${id}')">
            <i class="bx bx-pencil-sparkles"></i> Mejorar redacción
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" data-role="delete-politica"
                  data-bs-toggle="tooltip" data-bs-placement="top" title="Eliminar política">
            <i class="bx bx-trash"></i>
          </button>
        </div>
      </div>
    `;
        refs.container.appendChild(div);
        actualizarHidden();
    }

    function readPoliticas() {
        return $$('.politica-item textarea', refs.container)
            .map(t => t.value.trim())
            .filter(Boolean);
    }

    function actualizarHidden() {
        if (refs.hidden) refs.hidden.value = JSON.stringify(readPoliticas());
    }

    refs.container?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-role="delete-politica"]');
        if (!btn) return;
        btn.closest('.politica-item')?.remove();
        actualizarHidden();
    });

    refs.addBtn?.addEventListener('click', () => agregarPolitica());

    // Restaurar desde old()
    (function restorePoliticas() {
        if (!refs.hidden || !refs.hidden.value) return;
        try {
            const arr = JSON.parse(refs.hidden.value);
            (arr || []).forEach(p => agregarPolitica(p));
        } catch { }
    })();

    window.agregarPolitica = agregarPolitica;
    window.readPoliticas = readPoliticas;
})();

/* ========= Indicadores (toggle + lista) ========= */
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('toggleIndicadores');
    const section = document.getElementById('indicadoresSection');
    const addBtn = document.getElementById('addIndicador');
    const list = document.getElementById('indicadores-list');
    const hidden = document.getElementById('indicadores_json');

    let indicadores = [];

    function renderList() {
        list.innerHTML = '';
        indicadores.forEach((item, idx) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
        <span><strong>${item.indicador}</strong> — Meta: ${item.meta}, Monitoreo: ${item.monitoreo}, Responsable: ${item.responsable}</span>
        <button class="btn btn-sm btn-outline-danger" type="button">❌</button>
      `;
            li.querySelector('button').addEventListener('click', () => {
                indicadores.splice(idx, 1);
                hidden.value = JSON.stringify(indicadores);
                renderList();
            });
            list.appendChild(li);
        });
    }

    toggle.addEventListener('change', () => {
        section.style.display = toggle.checked ? 'block' : 'none';
        if (!toggle.checked) {
            indicadores = [];
            hidden.value = '[]';
            renderList();
        }
    });

    addBtn?.addEventListener('click', () => {
        const indicador = document.getElementById('indicador').value.trim();
        const meta = document.getElementById('meta').value.trim();
        const monitoreo = document.getElementById('monitoreo').value.trim();
        const responsable = document.getElementById('responsable').value.trim();
        if (!indicador || !meta || !monitoreo || !responsable) {
            alert('Por favor completa todos los campos del indicador.');
            return;
        }
        const item = {
            indicador,
            meta,
            monitoreo,
            responsable
        };
        indicadores.push(item);
        hidden.value = JSON.stringify(indicadores);
        renderList();
        ['indicador', 'meta', 'monitoreo', 'responsable'].forEach(id => document.getElementById(id)
            .value = '');
    });

    // Restaurar desde old()
    if (hidden.value) {
        try {
            const arr = JSON.parse(hidden.value);
            if (Array.isArray(arr) && arr.length) {
                indicadores = arr;
                toggle.checked = true;
                section.style.display = 'block';
                renderList();
            }
        } catch { }
    }
});

/* ========= Serialización antes de enviar (persistencia post-error) ========= */
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('procedimientoForm');
    form.addEventListener('submit', () => {
        // PASOS
        if (typeof readDesarrollo === 'function') {
            const desarrollo = readDesarrollo();
            document.getElementById('desarrollo_json').value = JSON.stringify(desarrollo || []);
        }
        // POLÍTICAS
        if (typeof readPoliticas === 'function') {
            const politicas = readPoliticas();
            document.getElementById('politicas_json').value = JSON.stringify(politicas || []);
        }
        // Indicadores ya están en su hidden en tiempo real
    });
});
// --- Utils ---
const splitCodigoNombre = (raw) => {
    // Intenta separar "CODIGO — NOMBRE" (em dash) o "CODIGO - NOMBRE"
    const parts = raw.split(/—| - | – /);
    if (parts.length >= 2) {
        const codigo = parts[0].trim();
        const nombre = parts.slice(1).join('—').trim();
        return {
            codigo,
            nombre
        };
    }
    // Si no hay separador, lo tratamos como "nombre" sin código
    return {
        codigo: '',
        nombre: raw.trim()
    };
};

function smartParseList(raw) {
    if (!raw) return [];
    try {
        const first = JSON.parse(raw);
        if (Array.isArray(first)) {
            // normaliza a {codigo,nombre}
            return first
                .filter(x => x && (x.codigo || x.nombre))
                .map(x => ({
                    codigo: x.codigo ?? x.nombre ?? '',
                    nombre: x.nombre ?? x.codigo ?? ''
                }));
        }
        if (typeof first === 'string') {
            // podría venir doblemente serializado
            try {
                const second = JSON.parse(first);
                if (Array.isArray(second)) {
                    return second
                        .filter(x => x && (x.codigo || x.nombre))
                        .map(x => ({
                            codigo: x.codigo ?? x.nombre ?? '',
                            nombre: x.nombre ?? x.codigo ?? ''
                        }));
                }
            } catch {
                /* ignore */
            }
            // fallback CSV
            return first.split(',').map(s => s.trim()).filter(Boolean).map(v => ({
                codigo: v,
                nombre: v
            }));
        }
    } catch {
        // fallback CSV puro
        return raw.split(',').map(s => s.trim()).filter(Boolean).map(v => ({
            codigo: v,
            nombre: v
        }));
    }
    return [];
}

function makeChipsMulti({
    inputId,
    chipsId,
    hiddenId,
    feedbackId,
    min = 1
}) {
    const input = document.getElementById(inputId);
    const box = document.getElementById(chipsId);
    const hid = document.getElementById(hiddenId);
    const fb = document.getElementById(feedbackId);

    if (!input || !box || !hid) return;

    // Estado
    let items = [];

    // Rehidratación desde old(): aceptamos string JSON o array directamente
    try {
        items = smartParseList(hid.value).filter(x => x && (x.codigo || x.nombre));
    } catch {
        items = [];
    }

    // Render chips
    const render = () => {
        box.querySelectorAll('.chip').forEach(c => c.remove());
        items.forEach((it, idx) => {
            const chip = document.createElement('span');
            chip.className = 'chip badge bg-primary d-flex align-items-center';
            chip.style.gap = '6px';
            chip.style.padding = '6px 10px';
            const label = it.codigo ? `[${it.codigo}] ${it.nombre}` : it.nombre;
            chip.innerHTML = `
        <span>${label}</span>
        <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Eliminar"></button>
      `;
            chip.querySelector('button').addEventListener('click', () => {
                items.splice(idx, 1);
                render();
            });
            box.appendChild(chip);
        });
        // Sincroniza hidden como JSON
        hid.value = JSON.stringify(items);
        // Validación visual
        const invalid = items.length < min;
        if (fb) fb.style.display = invalid ? 'block' : 'none';
        input.classList.toggle('is-invalid', invalid);
    };

    // Añadir item si no es duplicado (por etiqueta completa)
    const addCurrent = () => {
        const raw = (input.value || '').trim();
        if (!raw) return;
        const obj = splitCodigoNombre(raw);
        const key = obj.codigo ? (obj.codigo + '|' + obj.nombre) : obj.nombre;
        const exists = items.some(i => (i.codigo ? (i.codigo + '|' + i.nombre) : i.nombre) === key);
        if (!exists) {
            items.push(obj);
            render();
        }
        input.value = '';
    };

    // Eventos: Enter/Tab/Comma -> agrega
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === 'Tab' || e.key === ',') {
            e.preventDefault();
            addCurrent();
        }
        // Backspace con input vacío -> borra último
        if (e.key === 'Backspace' && input.value === '' && items.length) {
            items.pop();
            render();
        }
    });

    // Cambio tras seleccionar opción del datalist
    input.addEventListener('change', addCurrent);

    // Render inicial
    render();

    // API por si quieres consultarlo
    return {
        get value() {
            return items.slice();
        },
        validate() {
            return items.length >= min;
        }
    };
}

// Instancias
const refsCtrl = makeChipsMulti({
    inputId: 'ref_input',
    chipsId: 'chips_referencias',
    hiddenId: 'referencias_json',
    feedbackId: 'referencias_feedback',
    min: 1
});
const fmtsCtrl = makeChipsMulti({
    inputId: 'fmt_input',
    chipsId: 'chips_formatos',
    hiddenId: 'formatos_json',
    feedbackId: 'formatos_feedback',
    min: 1
});
// Referencias Normativas
const refnormCtrl = makeChipsMulti({
    inputId: 'refnorm_input',
    chipsId: 'chips_refnorm',
    hiddenId: 'refnorm_json',
    feedbackId: 'refnorm_feedback',
    min: 1
});
// Referencias Internas (nuevo)
const refintCtrl = makeChipsMulti({
    inputId: 'refint_input',
    chipsId: 'chips_refint',
    hiddenId: 'refint_json',
    feedbackId: 'refint_feedback',
    min: 1
});



// Validación al enviar (cliente)
document.getElementById('procedimientoForm')?.addEventListener('submit', (e) => {
    let ok = true;
    if (refsCtrl && !refsCtrl.validate()) ok = false;
    if (fmtsCtrl && !fmtsCtrl.validate()) ok = false;
    if (refintCtrl && !refintCtrl.validate()) ok = false;
    if (refnormCtrl && !refnormCtrl.validate()) ok = false;
    if (!ok) {
        e.preventDefault();
        e.stopPropagation();
        (document.querySelector('.is-invalid') || document.getElementById('referencias_feedback'))
            ?.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });
    }
});

// campo oculto en areas 

document.addEventListener('DOMContentLoaded', () => {
    const tipo = document.getElementById('tipo_documento');
    const area = document.getElementById('area_codigo');
    const consecutivo = document.getElementById('consecutivo');
    const fsc = document.getElementById('es_fsc');
    const codigo = document.getElementById('codigo');
    const elaboroArea = document.getElementById('elaboro_area');

    // Mapeo entre código y nombre de área
    const AREAS = {
        DG: 'Dirección General',
        DO: 'Dirección Operativa',
        CA: 'Control de Calidad',
        CO: 'Contabilidad',
        FA: 'Facturación',
        SI: 'Sistemas IT',
        MT: 'Mantenimiento',
        VE: 'Ventas',
        OP: 'Operaciones',
        EN: 'Entregas / Envíos',
        LI: 'Acabado Litografía',
        MA: 'Acabado Manual',
        AL: 'Almacén',
        ID: 'Integración y Desarrollo',
        OF: 'Oficinas',
    };

    /** Generar código como antes */
    function generarCodigo() {
        const tipoVal = tipo.value || '';
        const areaVal = area.value || '';
        const consecutivoVal = consecutivo.value.padStart(3, '0');
        const fscVal = fsc.checked ? 'FSC' : '';

        if (tipoVal && areaVal && consecutivoVal) {
            let partes = ['L', tipoVal];
            if (fscVal) partes.push(fscVal);
            partes.push(areaVal, consecutivoVal);
            codigo.value = partes.join('-');
        } else {
            codigo.value = '';
        }
    }

    /** Actualizar nombre completo en el campo oculto */
    function actualizarElaboroArea() {
        const codigoArea = area.value.trim().toUpperCase();
        if (AREAS[codigoArea]) {
            elaboroArea.value = AREAS[codigoArea];
        } else {
            elaboroArea.value = '';
        }
    }

    /** Escuchar cambios */
    [tipo, area, consecutivo, fsc].forEach(el => {
        el.addEventListener('input', generarCodigo);
        el.addEventListener('change', generarCodigo);
    });

    // Cuando cambie el área, también actualiza el campo oculto
    area.addEventListener('input', actualizarElaboroArea);
    area.addEventListener('change', actualizarElaboroArea);
});

// Descativada hasta que se muestre el diagrama
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnGenerarPDF');
    const preview = document.getElementById('mermaidPreview');

    /** Verifica si #mermaidPreview tiene una imagen renderizada visible */
    function verificarDiagrama() {
        if (!preview) return;

        const img = preview.querySelector('img');

        // Caso válido: hay una <img> y se ha cargado (tamaño mayor a 0)
        const diagramaListo = img && img.complete && img.naturalWidth > 0;

        if (diagramaListo) {
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    // 🧠 Observa cambios en el DOM dentro de #mermaidPreview
    const observer = new MutationObserver(() => verificarDiagrama());
    if (preview) {
        observer.observe(preview, {
            childList: true,
            subtree: true
        });
    }

    // También valida cada medio segundo por si la imagen se carga de forma tardía
    const interval = setInterval(verificarDiagrama, 500);

    // Primera validación al cargar
    verificarDiagrama();

    // Limpia el intervalo si el botón ya se habilitó
    const stopWhenReady = setInterval(() => {
        if (!btn.disabled) {
            clearInterval(interval);
            clearInterval(stopWhenReady);
        }
    }, 1000);
});
