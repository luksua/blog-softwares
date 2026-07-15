import { computed } from 'vue'

export const MENSAJE_FECHA_REQUERIDA = 'Debes indicar la fecha y hora en que se publicará el registro.'
export const MENSAJE_FECHA_PASADA = 'La fecha programada debe ser posterior al momento actual.'

/**
 * Fecha/hora mínima seleccionable para un input `datetime-local`
 * (el minuto actual, en horario local), y el validador que se usa antes
 * de enviar el formulario. Antes ambos vivían duplicados en
 * NewVersion.vue y EditVersion.vue.
 */
export function useFechaProgramada() {
  const fechaMinimaProgramada = computed(() => {
    const ahora = new Date()
    ahora.setSeconds(0, 0)
    ahora.setMinutes(ahora.getMinutes() - ahora.getTimezoneOffset())
    return ahora.toISOString().slice(0, 16)
  })

  /**
   * Devuelve `null` si la fecha es válida (presente y futura), o el
   * mensaje de error a mostrar si no lo es.
   */
  const validarFechaProgramada = (fecha: string | null | undefined): string | null => {
    if (!fecha) return MENSAJE_FECHA_REQUERIDA
    if (new Date(fecha).getTime() <= Date.now()) return MENSAJE_FECHA_PASADA
    return null
  }

  return { fechaMinimaProgramada, validarFechaProgramada }
}
