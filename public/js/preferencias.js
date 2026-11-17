/**
 * preferencias.js · Lectura y escritura segura en localStorage.
 * Todo va dentro de try/catch: localStorage puede no existir (modo privado, política del navegador)
 * o estar lleno, y la aplicación debe seguir funcionando sin recordar nada.
 * Solo se guardan preferencias de interfaz; nunca datos sensibles.
 */

/**
 * Lee y decodifica un valor guardado.
 * @param {string} clave
 * @param {*} porDefecto Valor que se devuelve si no hay nada o está corrupto.
 * @returns {*}
 */
export function cargar(clave, porDefecto) {
  try {
    const texto = localStorage.getItem(clave);
    return texto === null ? porDefecto : JSON.parse(texto);
  } catch {
    return porDefecto;
  }
}

/**
 * Guarda un valor (como JSON).
 * @param {string} clave
 * @param {*} valor
 * @returns {boolean} true si se pudo guardar.
 */
export function guardar(clave, valor) {
  try {
    localStorage.setItem(clave, JSON.stringify(valor));
    return true;
  } catch {
    return false;
  }
}

/**
 * Elimina un valor guardado.
 * @param {string} clave
 */
export function borrar(clave) {
  try {
    localStorage.removeItem(clave);
  } catch {
    /* Sin almacenamiento no hay nada que borrar. */
  }
}
