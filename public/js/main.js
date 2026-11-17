/**
 * main.js · Arranque del panel interactivo de incidencias.
 * Lee el JSON que PHP dejó en la página, sustituye la tabla de PHP por el panel y conecta los eventos.
 * Mejora progresiva: si algo falla aquí, la tabla de PHP sigue visible.
 */
import { Incidencia } from './incidencia.js';
import { cargar, guardar } from './preferencias.js';
import { resumir, filtrar, ordenar, construirPanel, pintarResumen, pintarPestanas, pintarTabla, PESTANAS, CAMPOS_ORDEN } from './vistas.js';

const CLAVE_PREFS = 'aulafix.panel';

/**
 * Lee y valida el JSON incrustado por el servidor.
 * @returns {Incidencia[]|null} Lista de incidencias, o null si el JSON no es válido.
 */
function leerDatos() {
  try {
    const nodo = document.getElementById('datos-incidencias');
    const datos = JSON.parse(nodo.textContent);
    return Array.isArray(datos) ? datos.map((d) => new Incidencia(d)) : null;
  } catch (error) {
    console.error('AulaFix: no se pudieron leer los datos', error);
    return null;
  }
}

/**
 * Recupera las preferencias guardadas, aceptando solo valores permitidos
 * (localStorage lo puede editar el usuario, así que no se fía de él).
 * @returns {{pestana:string, busqueda:string, prioridad:string, campo:string, asc:boolean}}
 */
function leerEstado() {
  const g = cargar(CLAVE_PREFS, {});
  return {
    pestana: PESTANAS.some(([v]) => v === g.pestana) ? g.pestana : 'todas',
    busqueda: typeof g.busqueda === 'string' ? g.busqueda.slice(0, 100) : '',
    prioridad: ['baja', 'media', 'alta'].includes(g.prioridad) ? g.prioridad : '',
    campo: CAMPOS_ORDEN.includes(g.campo) ? g.campo : 'fecha',
    asc: typeof g.asc === 'boolean' ? g.asc : false,
  };
}

/** Punto de entrada. */
function iniciar() {
  const incidencias = leerDatos();
  const tablaPhp = document.getElementById('tabla-php');
  if (incidencias === null || tablaPhp === null) {
    return; // Se queda la tabla de PHP.
  }

  const estado = leerEstado();
  const refs = construirPanel();
  refs.buscador.value = estado.busqueda;
  refs.selPrioridad.value = estado.prioridad;

  /** Recalcula todo lo que depende del estado y lo pinta. */
  function pintar() {
    const visibles = ordenar(filtrar(incidencias, estado), estado.campo, estado.asc);
    pintarResumen(refs, resumir(incidencias));
    pintarPestanas(refs, estado.pestana);
    pintarTabla(refs, visibles, incidencias.length, estado);
    guardar(CLAVE_PREFS, estado);
  }

  // Un único manejador en el contenedor de pestañas: closest() localiza la pestaña pulsada.
  refs.pestanas.addEventListener('click', (ev) => {
    const tab = ev.target.closest('[role="tab"]');
    if (tab) {
      estado.pestana = tab.dataset.pestana;
      pintar();
    }
  });

  // Un único manejador en la cabecera de la tabla: otro clic en la misma columna invierte el orden.
  refs.cabecera.addEventListener('click', (ev) => {
    const th = ev.target.closest('th[data-orden]');
    if (th) {
      const campo = th.dataset.orden;
      estado.asc = estado.campo === campo ? !estado.asc : true;
      estado.campo = campo;
      pintar();
    }
  });

  // «input» filtra mientras se escribe.
  refs.buscador.addEventListener('input', () => {
    estado.busqueda = refs.buscador.value;
    pintar();
  });
  refs.selPrioridad.addEventListener('change', () => {
    estado.prioridad = refs.selPrioridad.value;
    pintar();
  });

  tablaPhp.replaceWith(refs.raiz);
  pintar();
  // Refresca los «hace X» cada minuto sin que haya que recargar la página.
  setInterval(pintar, 60000);
}

iniciar();
