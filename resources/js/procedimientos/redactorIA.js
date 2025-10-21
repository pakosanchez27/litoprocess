/** Parser SSE robusto: concatena múltiples líneas `data:` por evento y extrae deltas de texto */
function* parseSSE(bufferChunk) {
  const events = bufferChunk.split(/\n\n/); // eventos separados por línea en blanco
  for (const evt of events) {
    if (!evt) continue;
    const lines = evt.split('\n').filter(l => l.startsWith('data:'));
    if (!lines.length) continue;

    const payload = lines.map(l => l.slice(5).trim()).join('\n');
    if (!payload || payload === '[DONE]') continue;

    try {
      const json = JSON.parse(payload);

      // OpenAI Responses SSE
      if (json.type === 'response.output_text.delta' && json.delta) {
        yield json.delta;
        continue;
      }

      // Variante compatible (algunos SDKs)
      if (json.type === 'message.delta' && json?.delta?.content?.[0]?.text) {
        yield json.delta.content[0].text;
        continue;
      }

      // Gemini reempaquetado (opcional)
      const t = json?.candidates?.[0]?.content?.parts?.[0]?.text;
      if (t) yield t;

    } catch (_) {
      // Ignora fragmentos que aún no cierran JSON
    }
  }
}

/** Controladores por textarea para permitir cancelación */
const __redactIAControllers = new Map();

/** Redacta con IA (SSE) y pinta en tiempo real en el textarea indicado */
async function redactarIA({
  textareaId = 'objetivo',
  type = 'objetivo',
  url = '/ia/redactar'
} = {}) {
  const ta = document.getElementById(textareaId);
  if (!ta) throw new Error(`No se encontró textarea #${textareaId}`);

  const tokenMeta = document.querySelector('meta[name="csrf-token"]');
  const token = tokenMeta?.content || '';

  // UX: preparar
  const orig = ta.value;
  const prevPH = ta.getAttribute('placeholder') || '';
  ta.value = '';
  ta.setAttribute('placeholder', 'Mejorando redacción…');

  let finalText = '';

  // Si había una solicitud previa para este textarea, cancélala
  const prevController = __redactIAControllers.get(textareaId);
  if (prevController) prevController.abort();

  const controller = new AbortController();
  __redactIAControllers.set(textareaId, controller);

  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'text/event-stream',
        ...(token ? { 'X-CSRF-TOKEN': token } : {})
      },
      credentials: 'same-origin',
      body: JSON.stringify({ text: orig, type }),
      signal: controller.signal
    });

    if (!res.ok || !res.body) throw new Error('No se pudo abrir el stream');

    const reader  = res.body.getReader();
    const decoder = new TextDecoder();
    let buffer    = '';

    while (true) {
      const { value, done } = await reader.read();
      if (done) break;

      // Decodificar y normalizar CRLF -> LF
      buffer += decoder.decode(value, { stream: true }).replace(/\r\n/g, '\n');

      // Procesar eventos completos (doble salto de línea)
      const complete = buffer.lastIndexOf('\n\n');
      if (complete >= 0) {
        const chunk = buffer.slice(0, complete + 2);
        buffer = buffer.slice(complete + 2);

        for (const delta of parseSSE(chunk)) {
          finalText += delta;
          ta.value   = finalText;
          if (window.updateCounters) window.updateCounters();
          if (window.persist) window.persist();
        }
      }
    }

    // Tail del buffer (por si quedó algo suelto)
    if (buffer) {
      for (const delta of parseSSE(buffer)) {
        finalText += delta;
        ta.value   = finalText;
      }
    }

    ta.setAttribute('placeholder', prevPH);
    __redactIAControllers.delete(textareaId);
    return finalText;

  } catch (err) {
    // Si fue cancelación, no restaures valor original (opcional).
    if (err.name !== 'AbortError') {
      console.error(err);
      ta.value = orig;
    }
    ta.setAttribute('placeholder', prevPH);
    __redactIAControllers.delete(textareaId);
    throw err;
  }
}

/** Helpers por tipo para mantener tu API original */
window.redactarObjetivoIA = () => redactarIA({ textareaId: 'objetivo', type: 'objetivo' });
window.redactarAlcanceIA  = () => redactarIA({ textareaId: 'alcance',  type: 'alcance'  });
window.mejorarDefinicionIA = () => redactarIA({ textareaId: 'definicion', type: 'definicion' });
window.redactarActividadIA  = (id) => redactarIA({ textareaId: id,  type: 'actividad'  });
window.redactarPoliticasIA = (id) => redactarIA({ textareaId: id, type: 'politicas' });
/** Cancelar en curso si lo necesitas manualmente */
window.cancelRedaccionIA = (textareaId) => {
  const c = __redactIAControllers.get(textareaId);
  if (c) c.abort();
};


