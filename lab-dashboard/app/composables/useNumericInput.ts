/**
 * Composable para inputs numéricos con:
 * - Formato visual con puntos de miles: 1000 → "1.000"
 * - Decimales con coma: 1000.5 → "1.000,50"
 * - Bloqueo absoluto de valores negativos
 * - Separación entre valor mostrado (string formateado) y valor real (number)
 *
 * Uso:
 *   const { display, raw, onInput, onKeydown } = useNumericInput(initialValue, { decimals: 2 })
 *   <input :value="display" @input="onInput" @keydown="onKeydown" />
 *   // raw.value contiene el número limpio para enviar al backend
 */

import { ref, computed, nextTick } from 'vue'

interface NumericInputOptions {
  decimals?: number   // cuántos decimales permitir (0 = entero, default: 2)
  min?: number        // valor mínimo (default: 0, bloquea negativos)
  allowEmpty?: boolean // permitir campo vacío (default: true)
}

export function useNumericInput(initial: number | string = '', options: NumericInputOptions = {}) {
  const { decimals = 2, min = 0, allowEmpty = true } = options

  // Convierte número real a string formateado con puntos de miles y coma decimal
  function toDisplay(val: number | string): string {
    if (val === '' || val === null || val === undefined) return ''
    const n = typeof val === 'string' ? parseFloat(String(val).replace(/\./g, '').replace(',', '.')) : val
    if (isNaN(n)) return ''
    if (decimals === 0) {
      return Math.max(min, Math.floor(n)).toLocaleString('de-DE')
    }
    return Math.max(min, n).toLocaleString('de-DE', {
      minimumFractionDigits: 0,
      maximumFractionDigits: decimals
    })
  }

  // Extrae el número real desde un string formateado (con puntos y coma)
  function toRaw(formatted: string): number {
    if (!formatted) return 0
    // Elimina puntos de miles, reemplaza coma decimal por punto
    const cleaned = formatted.replace(/\./g, '').replace(',', '.')
    const n = parseFloat(cleaned)
    if (isNaN(n)) return 0
    return Math.max(min, n)
  }

  const display = ref<string>(toDisplay(initial))
  const raw = computed<number>(() => toRaw(display.value))

  function onKeydown(e: KeyboardEvent) {
    // Bloquear tecla '-' y 'e' (notación científica)
    if (e.key === '-' || e.key === 'e' || e.key === 'E') {
      e.preventDefault()
      return
    }

    if (e.key === '.') {
      e.preventDefault()
      const input = e.target as HTMLInputElement
      const start = input.selectionStart ?? 0
      const end = input.selectionEnd ?? 0
      const val = input.value
      // Solo insertar coma si no hay una ya
      if (!val.includes(',')) {
        input.value = val.substring(0, start) + ',' + val.substring(end)
        input.setSelectionRange(start + 1, start + 1)
        input.dispatchEvent(new Event('input', { bubbles: true }))
      }
      return
    }

    // Permitir: dígitos, coma, backspace, delete, tab, arrows, ctrl+a/c/v/x
    const allowed = [
      'Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight',
      'Home', 'End', ','
    ]
    if (
      allowed.includes(e.key) ||
      (e.ctrlKey || e.metaKey) ||
      /^\d$/.test(e.key)
    ) return

    e.preventDefault()
  }

  function onInput(e: Event) {
    const input = e.target as HTMLInputElement
    const cursorPos = input.selectionStart ?? 0

    // Limpiar puntos (que son separadores de miles generados por nosotros)
    let raw = input.value.replace(/\./g, '')
    
    // Solo dejar números y coma
    raw = raw.replace(/[^\d,]/g, '')

    // Solo permitir una coma
    const parts = raw.split(',')
    if (parts.length > 2) {
      raw = parts[0] + ',' + parts.slice(1).join('')
    }

    // Parte entera y decimal
    let intPart = (parts[0] || '').replace(/^0+(?=\d)/, '') || '0'
    let decPart = parts[1]

    // Aplicar puntos de miles a la parte entera
    intPart = parseInt(intPart || '0', 10).toLocaleString('de-DE')

    // Reconstruir
    let formatted: string
    if (parts.length === 2) {
      // Usuario escribió coma — mostrar decimales tal como los escribe
      const trimmedDec = (decPart ?? '').slice(0, decimals)
      formatted = `${intPart},${trimmedDec}`
    } else {
      formatted = intPart === '0' && allowEmpty && !input.value ? '' : intPart
    }

    // Si el valor es 0 y el campo está limpio, mostrar vacío
    if (formatted === '0' && (input.value === '' || input.value === '0')) {
      formatted = allowEmpty ? '' : '0'
    }

    display.value = formatted
    input.value = formatted

    // Restaurar posición del cursor ajustada por los puntos insertados
    const diff = formatted.length - input.value.length
    const newPos = Math.max(0, cursorPos + diff)
    nextTick(() => {
      input.setSelectionRange(newPos, newPos)
    })
  }

  function setValue(val: number | string) {
    display.value = toDisplay(val)
  }

  function reset() {
    display.value = allowEmpty ? '' : '0'
  }

  return { display, raw, onInput, onKeydown, setValue, reset, toRaw, toDisplay }
}

/**
 * Versión simplificada para usar con v-model directo en refs de string existentes.
 * Formatea el valor de un ref<string> al blur y bloquea negativos al keydown.
 *
 * Uso en template:
 *   <input :value="formatDisplay(myRef)" @input="handleRawInput(myRef, $event)" @keydown="blockNegative" @blur="formatOnBlur(myRef)" />
 */
export function useNumericFormat() {
  function formatThousands(val: number | string, decimals = 2): string {
    if (val === '' || val === null || val === undefined) return ''
    const s = String(val).replace(/\./g, '').replace(',', '.')
    const n = parseFloat(s)
    if (isNaN(n) || n < 0) return '0'
    return n.toLocaleString('de-DE', {
      minimumFractionDigits: 0,
      maximumFractionDigits: decimals
    })
  }

  function parseFormatted(val: string): number {
    if (!val) return 0
    const n = parseFloat(val.replace(/\./g, '').replace(',', '.'))
    return isNaN(n) || n < 0 ? 0 : n
  }

  function blockNegative(e: KeyboardEvent) {
    if (e.key === '-' || e.key === 'e' || e.key === 'E') e.preventDefault()
  }

  return { formatThousands, parseFormatted, blockNegative }
}
