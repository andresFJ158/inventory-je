/**
 * Utilidades de formato compartidas.
 * Antes estaban duplicadas (decode/fmt) en 7+ páginas.
 * Auto-importadas por Nuxt desde app/utils.
 */

/** Decodifica strings urlencoded del backend (espacios como '+'). */
export function decodeText(s: string | null | undefined): string {
  if (!s) return ''
  try {
    return decodeURIComponent(String(s)).replace(/\+/g, ' ')
  } catch {
    return String(s).replace(/\+/g, ' ')
  }
}

/** Formatea un valor como moneda boliviana (Bs.). */
export function formatBob(v: number | string | null | undefined): string {
  const n = typeof v === 'string' ? parseFloat(v) : (v ?? 0)
  return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(Number.isFinite(n) ? n : 0)
}
