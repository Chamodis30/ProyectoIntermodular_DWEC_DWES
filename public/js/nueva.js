/**
 * nueva.js · Ayudas del formulario nueva.php: contador de caracteres y borrador en localStorage.
 */
import { cargar, guardar, borrar } from './preferencias.js';

const CLAVE_BORRADOR = 'aulafix.borrador';
const MAXIMO = 500;

/**
 * Muestra «n/500» junto a la descripción.
 * @param {HTMLTextAreaElement} campo
 * @param {HTMLElement} salida
 */
function actualizarContador(campo, salida) {
  salida.textContent = `${campo.value.length}/${MAXIMO} caracteres`;
}

/** Punto de entrada. */
function iniciar() {
  const campo = document.getElementById('descripcion');
  const salida = document.getElementById('contador-descripcion');
  const formulario = document.getElementById('form-nueva');
  if (!campo || !salida || !formulario) {
    return;
  }

  // Si el servidor devolvió el texto (por un error de validación) manda el del servidor;
  // el borrador solo se recupera cuando el campo está vacío.
  if (campo.value === '') {
    const borrador = cargar(CLAVE_BORRADOR, '');
    if (typeof borrador === 'string') {
      campo.value = borrador.slice(0, MAXIMO);
    }
  }
  actualizarContador(campo, salida);

  campo.addEventListener('input', () => {
    actualizarContador(campo, salida);
    guardar(CLAVE_BORRADOR, campo.value); // Solo el texto de la descripción: nada sensible.
  });
  // Al enviar, el borrador ya no hace falta (el servidor conserva el texto si hay errores).
  formulario.addEventListener('submit', () => borrar(CLAVE_BORRADOR));
}

iniciar();
