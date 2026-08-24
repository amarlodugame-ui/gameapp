import axios, { AxiosError, type InternalAxiosRequestConfig } from 'axios'

type SpaAxiosConfig = InternalAxiosRequestConfig & {
    _spaCsrfRetried?: boolean
    _spaSkipUnauthorized?: boolean
}

let unauthorizedHandler: (() => void) | null = null
let authLossHandler: ((payload: any) => void) | null = null

function csrfMeta(): HTMLMetaElement | null {
    return document.querySelector('meta[name="csrf-token"]')
}

export function setCsrfToken(token?: string | null): void {
    if (!token) return

    axios.defaults.headers.common['X-CSRF-TOKEN'] = token

    const meta = csrfMeta()
    if (meta) meta.content = token
}

export function onUnauthorized(handler: () => void): void {
    unauthorizedHandler = handler
}

export function onAuthLoss(handler: (payload: any) => void): void {
    authLossHandler = handler
}

axios.defaults.withCredentials = true
axios.defaults.withXSRFToken = true
axios.defaults.headers.common.Accept = 'application/json'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['X-User-Spa'] = '1'
setCsrfToken(csrfMeta()?.content)

async function renewCsrfToken(): Promise<string | null> {
    const response = await fetch('/user/spa/session', {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-User-Spa': '1',
        },
    })

    if (!response.ok) return null

    const payload = await response.json()
    const session = payload?.data?.authenticated !== undefined ? payload.data : payload
    const token = session?.csrf_token ?? null
    setCsrfToken(token)

    return token
}

axios.interceptors.response.use(
    (response) => response,
    async (error: AxiosError) => {
        const status = error.response?.status
        const config = error.config as SpaAxiosConfig | undefined

        if (status === 419 && config && !config._spaCsrfRetried) {
            config._spaCsrfRetried = true
            const token = await renewCsrfToken()

            if (token) {
                config.headers.set('X-CSRF-TOKEN', token)
                return axios(config)
            }
        }

        if (status === 401 && !config?._spaSkipUnauthorized) {
            unauthorizedHandler?.()
        }

        const payload = error.response?.data as any
        if (status === 403
            && !config?._spaSkipUnauthorized
            && payload?.authenticated === false
            && payload?.redirect) {
            authLossHandler?.(payload)
        }

        return Promise.reject(error)
    },
)

export default axios
