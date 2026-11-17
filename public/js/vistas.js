/**
 * vistas.js · Lógica del panel: funciones puras (resumir, filtrar, ordenar) y pintado con el DOM.
 * Los datos de usuarios se insertan SIEMPRE con textContent/append de texto, nunca con innerHTML,
 * porque innerHTML interpretaría etiquetas escritas por un usuario (XSS).
 */
import { formatearFechaCompleta, haceCuanto } from './fechas.js';

/** Pestañas del panel: [valor, etiqueta]. */
export const PESTANAS = [['todas', 'Todas'], ['abierta', 'Abiertas'], ['en_curso', 'En curso'], ['resuelta', 'Resueltas']];
/** Campos por los que se puede ordenar. */
export const CAMPOS_ORDEN = ['fecha', 'equipo', 'prioridad'];

const PESO_PRIORIDAD = { baja: 1, media: 2, alta: 3 };

/* ---------- Funciones puras (no tocan el DOM ni modifican la lista recibida) ---------- */

/**
 * Cuenta incidencias por estado y las urgentes, recorriendo la lista una sola vez con reduce.
 * @param {import('./incidencia.js').Incidencia[]} lista
 * @returns {{abierta:number, en_curso:number, resuelta:number, urgentes:number}}
 */
export function resumir(lista) {
  return lista.reduce(
    (acc, inc) => {
      if (inc.estado in acc && inc.estado !== 'urgentes') {
        acc[inc.estado] += 1;
      }
      if (inc.esUrgente) {
        acc.urgentes += 1;
      }
      return acc;
    },
    { abierta: 0, en_curso: 0, resuelta: 0, urgentes: 0 }
  );
}

/**
 * Filtra por pestaña, prioridad y texto (equipo, descripción o autor). Devuelve un array NUEVO.
 * @param {import('./incidencia.js').Incidencia[]} lista
 * @param {{pestana:string, busqueda:string, prioridad:string}} f
 * @returns {import('./incidencia.js').Incidencia[]}
 */
export function filtrar(lista, { pestana, busqueda, prioridad }) {
  const q = busqueda.trim().toLowerCase();
  return lista.filter(
    (i) =>
      (pestana === 'todas' || i.estado === pestana) &&
      (prioridad === '' || i.prioridad === prioridad) &&
      (q === '' || [i.equipo, i.descripcion, i.autor].some((t) => t.toLowerCase().includes(q)))
  );
}

/**
 * Ordena sin modificar el array original: sort() cambia el array sobre el que se llama, y
 * alterar la lista original rompería los demás filtros y el orden «natural» de los datos.
 * Por eso se ordena una copia ([...lista]).
 * @param {import('./incidencia.js').Incidencia[]} lista
 * @param {string} campo 'fecha' | 'equipo' | 'prioridad'
 * @param {boolean} ascendente
 * @returns {import('./incidencia.js').Incidencia[]}
 */
export function ordenar(lista, campo, ascendente) {
  const comparar = {
    fecha: (a, b) => a.creada - b.creada,
    equipo: (a, b) => a.equipo.localeCompare(b.equipo, 'es', { numeric: true }),
    prioridad: (a, b) => PESO_PRIORIDAD[a.prioridad] - PESO_PRIORIDAD[b.prioridad],
  }[campo] ?? (() => 0);
  const signo = ascendente ? 1 : -1;
  return [...lista].sort((a, b) => signo * comparar(a, b));
}

/* ---------- Pintado con el DOM ---------- */

/**
 * Crea un elemento. Los textos entran por textContent (seguro).
 * @param {string} etiqueta
 * @param {Object<string,string>} [props] 'clase' → className, 'texto' → textContent, resto → atributos.
 * @param {...(Node|string)} hijos
 * @returns {HTMLElement}
 */
function el(etiqueta, props = {}, ...hijos) {
  const nodo = document.createElement(etiqueta);
  for (const [clave, valor] of Object.entries(props)) {
    if (clave === 'clase') nodo.className = valor;
    else if (clave === 'texto') nodo.textContent = valor;
    else nodo.setAttribute(clave, valor);
  }
  nodo.append(...hijos);
  return nodo;
}

/**
 * Construye la estructura vacía del panel y devuelve las referencias a sus partes.
 * @returns {{raiz:HTMLElement, cifras:Object<string,HTMLElement>, pestanas:HTMLElement, buscador:HTMLInputElement,
 *            selPrioridad:HTMLSelectElement, cabecera:HTMLElement, cuerpo:HTMLElement, contador:HTMLElement}}
 */
export function construirPanel() {
  const tarjetas = [['abierta', 'Abiertas'], ['en_curso', 'En curso'], ['resuelta', 'Resueltas'], ['urgentes', 'Urgentes sin resolver']];
  const cifras = {};
  const resumen = el('section', { clase: 'resumen', 'aria-label': 'Resumen' });
  for (const [clave, etiqueta] of tarjetas) {
    cifras[clave] = el('strong', { texto: '0' });
    resumen.append(el('div', { clase: 'tarjeta' + (clave === 'urgentes' ? ' tarjeta-urgente' : '') }, cifras[clave], el('span', { texto: etiqueta })));
  }

  const pestanas = el('div', { role: 'tablist', 'aria-label': 'Estado' });
  for (const [valor, etiqueta] of PESTANAS) {
    pestanas.append(el('button', { type: 'button', role: 'tab', 'data-pestana': valor, texto: etiqueta, 'aria-selected': 'false' }));
  }

  const buscador = el('input', { type: 'search', id: 'buscador', placeholder: 'Equipo, descripción o autor' });
  const selPrioridad = el('select', { id: 'filtro-prioridad' },
    el('option', { value: '', texto: 'Todas las prioridades' }),
    ...['baja', 'media', 'alta'].map((p) => el('option', { value: p, texto: p })));
  const filtros = el('div', { clase: 'filtros' },
    el('label', { for: 'buscador', texto: 'Buscar' }), buscador,
    el('label', { for: 'filtro-prioridad', texto: 'Prioridad' }), selPrioridad);

  // aria-live: los lectores de pantalla anuncian el contador cada vez que cambia.
  const contador = el('p', { 'aria-live': 'polite', clase: 'contador' });

  const cabecera = el('tr');
  for (const [campo, texto] of [['fecha', 'Fecha'], ['equipo', 'Equipo'], ['prioridad', 'Prioridad']]) {
    cabecera.append(el('th', { scope: 'col', 'data-orden': campo, 'aria-sort': 'none' }, el('button', { type: 'button', texto })));
  }
  for (const texto of ['Estado', 'Descripción', 'Autor', '']) {
    cabecera.append(el('th', { scope: 'col', texto }));
  }
  const cuerpo = el('tbody');
  const tabla = el('table', { clase: 'tabla-panel' }, el('caption', { texto: 'Incidencias', clase: 'solo-lectores' }), el('thead', {}, cabecera), cuerpo);

  const raiz = el('div', { id: 'panel' }, resumen, pestanas, filtros, contador, el('div', { clase: 'tabla-scroll' }, tabla));
  return { raiz, cifras, pestanas, buscador, selPrioridad, cabecera, cuerpo, contador };
}

/**
 * Actualiza las cuatro tarjetas del resumen.
 * @param {ReturnType<typeof construirPanel>} refs
 * @param {{abierta:number, en_curso:number, resuelta:number, urgentes:number}} datos
 */
export function pintarResumen(refs, datos) {
  for (const [clave, nodo] of Object.entries(refs.cifras)) {
    nodo.textContent = String(datos[clave]);
  }
}

/**
 * Marca la pestaña activa con aria-selected (y roving tabindex para el teclado).
 * @param {ReturnType<typeof construirPanel>} refs
 * @param {string} activa
 */
export function pintarPestanas(refs, activa) {
  for (const tab of refs.pestanas.querySelectorAll('[role="tab"]')) {
    const seleccionada = tab.dataset.pestana === activa;
    tab.setAttribute('aria-selected', String(seleccionada));
    tab.tabIndex = seleccionada ? 0 : -1;
  }
}

/**
 * Repinta la tabla: indicador de orden (aria-sort), filas y contador.
 * Las filas se montan en un DocumentFragment y se insertan de una vez (un solo reflujo del navegador).
 * @param {ReturnType<typeof construirPanel>} refs
 * @param {import('./incidencia.js').Incidencia[]} visibles Incidencias ya filtradas y ordenadas.
 * @param {number} total Total de incidencias sin filtrar.
 * @param {{campo:string, asc:boolean}} orden
 * @param {Date} [ahora=new Date()]
 */
export function pintarTabla(refs, visibles, total, orden, ahora = new Date()) {
  for (const th of refs.cabecera.querySelectorAll('th[data-orden]')) {
    const activo = th.dataset.orden === orden.campo;
    th.setAttribute('aria-sort', activo ? (orden.asc ? 'ascending' : 'descending') : 'none');
  }

  const fragmento = document.createDocumentFragment();
  for (const inc of visibles) {
    const fila = el('tr', { clase: inc.esUrgente ? 'urgente' : '' });
    // <time title> muestra la fecha completa al pasar el ratón; el texto visible es «hace X».
    const fecha = el('time', { datetime: Number.isNaN(inc.creada.getTime()) ? '' : inc.creada.toISOString(), title: formatearFechaCompleta(inc.creada), texto: haceCuanto(inc.creada, ahora) });
    const enlace = el('a', { href: `incidencia.php?id=${encodeURIComponent(inc.id)}`, texto: 'Ver' });
    fila.append(
      el('td', {}, fecha), el('td', { texto: inc.equipo }), el('td', { texto: inc.prioridad }),
      el('td', { texto: inc.estado.replace('_', ' ') }), el('td', { texto: inc.descripcion }),
      el('td', { texto: inc.autor }), el('td', {}, enlace)
    );
    fragmento.append(fila);
  }
  if (visibles.length === 0) {
    // Caso límite: lista vacía.
    fragmento.append(el('tr', {}, el('td', { colspan: '7', texto: 'No hay incidencias que coincidan.' })));
  }
  refs.cuerpo.replaceChildren(fragmento);
  refs.contador.textContent = `Mostrando ${visibles.length} de ${total} incidencias`;
}
