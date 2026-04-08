const trimTrailingSlash = (value = '') => value.toString().replace(/\/+$/, '')
const isAbsoluteHttpUrl = (value = '') => /^https?:\/\//i.test(String(value || '').trim())

const sanitizeRuntimeOrigin = (value = '') => {
  const raw = trimTrailingSlash(value)
  if (!raw || !isAbsoluteHttpUrl(raw)) {
    return ''
  }

  try {
    const parsed = new URL(raw)
    return trimTrailingSlash(parsed.origin)
  } catch {
    return ''
  }
}

const isLoopbackHost = (host = '') => {
  const normalized = String(host || '').toLowerCase()
  return normalized === 'localhost' || normalized === '127.0.0.1'
}

export const getApiOrigin = () => {
  const runtimeWindow = globalThis?.window
  const currentHost = (runtimeWindow?.location?.hostname || '').toLowerCase()

  const envOrigin = trimTrailingSlash(process.env.VUE_APP_API_BASE_URL || '')
  if (envOrigin) {
    // If frontend is opened via a non-local host/tunnel, loopback API URLs are unreachable from that browser.
    // In that case prefer same-origin to avoid hard network failures.
    try {
      const envHost = (new URL(envOrigin)).hostname.toLowerCase()
      if (isLoopbackHost(envHost) && currentHost && !isLoopbackHost(currentHost)) {
        return trimTrailingSlash(runtimeWindow?.location?.origin || envOrigin)
      }
    } catch {
      // Keep env origin when parsing fails.
    }

    return envOrigin
  }

  // Allow manual override in browser without rebuilding the app.
  const runtimeOverride = sanitizeRuntimeOrigin(
    (typeof localStorage !== 'undefined' && localStorage.getItem('chatapp.api_origin')) || ''
  )
  if (runtimeOverride) {
    return runtimeOverride
  }

  const hostname = currentHost
  const isLocalHost = hostname === 'localhost' || hostname === '127.0.0.1'
  if (isLocalHost) {
    const protocol = runtimeWindow?.location?.protocol || 'http:'
    return `${protocol}//${hostname}:8000`
  }

  // Common local dev setup: frontend on 3000/8080, API on 8000 at the same host.
  const devPorts = new Set(['3000', '8080'])
  const currentPort = String(runtimeWindow?.location?.port || '')
  if (devPorts.has(currentPort)) {
    const protocol = runtimeWindow?.location?.protocol || 'http:'
    return `${protocol}//${hostname}:8000`
  }

  // For non-local hosts, default to same origin to avoid unreachable localhost URLs.
  return trimTrailingSlash(runtimeWindow?.location?.origin || '')
}

export const getApiBaseUrl = () => `${trimTrailingSlash(getApiOrigin())}/api`

