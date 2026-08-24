import { reactive } from 'vue'
import http, { setCsrfToken } from './http'

export interface SessionUser {
    id: number | string
    name?: string | null
    image?: number | string | null
    status?: number | string | null
    [key: string]: unknown
}

interface SessionState {
    loaded: boolean
    loading: boolean
    authenticated: boolean
    user: SessionUser | null
    csrfToken: string | null
}

export const session = reactive<SessionState>({
    loaded: false,
    loading: false,
    authenticated: false,
    user: null,
    csrfToken: null,
})

let pendingSession: Promise<SessionState> | null = null

function unwrapSession(payload: any): any {
    if (payload?.data && typeof payload.data.authenticated === 'boolean') {
        return payload.data
    }

    return payload ?? {}
}

export async function loadSession(force = false): Promise<SessionState> {
    if (session.loaded && !force) return session
    if (pendingSession) return pendingSession

    session.loading = true

    pendingSession = http
        .get('/user/spa/session', {
            _spaSkipUnauthorized: true,
        } as any)
        .then(({ data }) => {
            const payload = unwrapSession(data)
            session.authenticated = Boolean(payload.authenticated)
            session.user = payload.user ?? null
            session.csrfToken = payload.csrf_token ?? null
            session.loaded = true
            setCsrfToken(session.csrfToken)
            return session
        })
        .catch((error) => {
            if (error?.response?.status === 401) {
                clearSession()
                return session
            }

            throw error
        })
        .finally(() => {
            session.loading = false
            pendingSession = null
        })

    return pendingSession
}

export function syncSessionFromPageProps(props: Record<string, any>): void {
    if (!props?.auth || !Object.prototype.hasOwnProperty.call(props.auth, 'user')) return

    const pageUser = props.auth.user
    session.user = pageUser
        ? { ...(session.user ?? {}), ...pageUser } as SessionUser
        : null
    session.authenticated = Boolean(session.user)
    session.loaded = true
}

export function clearSession(csrfToken: string | null | undefined = undefined): void {
    session.loaded = true
    session.loading = false
    session.authenticated = false
    session.user = null

    if (csrfToken !== undefined) {
        session.csrfToken = csrfToken
        setCsrfToken(csrfToken)
    }
}
