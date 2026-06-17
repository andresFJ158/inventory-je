export function getImageUrl(imgStr: string): string {
  if (!imgStr) return ''
  const decoded = decodeURIComponent(imgStr).replace(/\+/g, ' ')
  const viewsIdx = decoded.indexOf('views/')
  if (viewsIdx !== -1) return '/' + decoded.substring(viewsIdx)
  if (decoded.startsWith('http') || decoded.startsWith('/')) return decoded
  return '/' + decoded
}
