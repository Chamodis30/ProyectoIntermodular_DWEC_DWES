/**
 * incidencia.js · Clase Incidencia: envuelve los datos JSON del servidor y añade propiedades calculadas.
 */

export class Incidencia {
  /** Fecha de creación. Privada: solo se expone una copia, para que nadie la modifique desde fuera. */
  #creada;

  /**
   * @param {{id:number, equipo:string, categoria:string, prioridad:string, descripcion:string,
   *          estado:string, autor:string, creada_en:string}} datos Objeto del JSON (contrato con PHP).
   */
  constructor(datos) {
    this.id = Number(datos.id);
    this.equipo = String(datos.equipo);
    this.categoria = String(datos.categoria);
    this.prioridad = String(datos.prioridad);
    this.descripcion = String(datos.descripcion);
    this.estado = String(datos.estado);
    this.autor = String(datos.autor);
    this.#creada = new Date(datos.creada_en);
  }

  /** @returns {Date} Copia de la fecha de creación. */
  get creada() {
    return new Date(this.#creada.getTime());
  }

  /** @returns {boolean} Urgente = prioridad alta y todavía no resuelta. */
  get esUrgente() {
    return this.prioridad === 'alta' && this.estado !== 'resuelta';
  }

  /** @returns {number} Días enteros desde que se creó (0 si es de hoy o la fecha es inválida). */
  get diasAbierta() {
    const dias = Math.floor((Date.now() - this.#creada.getTime()) / 86400000);
    return Number.isFinite(dias) ? Math.max(0, dias) : 0;
  }
}
