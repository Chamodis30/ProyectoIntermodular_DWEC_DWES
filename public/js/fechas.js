/**
 * fechas.js · Formato de fechas en español con Intl: «hace 2 días» y fecha completa.
 */

const relativo = new Intl.RelativeTimeFormat('es', { numeric: 'auto' });
const completo = new Intl.DateTimeFormat('es-ES', { dateStyle: 'full', timeStyle: 'short' });

/**
 * Indica si un Date es válido (new Date('basura') produce «Invalid Date»).
 * @param {Date} fecha
 * @returns {boolean}
 */
function esValida(fecha) {
  return fecha instanceof Date && !Number.isNaN(fecha.getTime());
}

/**
 * Fecha completa en español, p. ej. «lunes, 2 de noviembre de 2026, 18:30».
 * @param {Date} fecha
 * @returns {string} Texto, o «Fecha desconocida» si el Date no es válido.
 */
export function formatearFechaCompleta(fecha) {
  return esValida(fecha) ? completo.format(fecha) : 'Fecha desconocida';
}

/**
 * Tiempo transcurrido en lenguaje natural («hace 2 días», «hace 3 horas»).
 * @param {Date} fecha Momento pasado.
 * @param {Date} [ahora=new Date()] Momento de referencia (parámetro para poder probar la función).
 * @returns {string} Texto relativo, o «—» si la fecha no es válida.
 */
export function haceCuanto(fecha, ahora = new Date()) {
  if (!esValida(fecha)) {
    return '—';
  }
  const segundos = Math.round((fecha.getTime() - ahora.getTime()) / 1000);
  const tramos = [['day', 86400], ['hour', 3600], ['minute', 60]];
  for (const [unidad, tamano] of tramos) {
    if (Math.abs(segundos) >= tamano) {
      // trunc (no round): 1,9 días sigue siendo «hace 1 día».
      return relativo.format(Math.trunc(segundos / tamano), unidad);
    }
  }
  return 'ahora mismo';
}
