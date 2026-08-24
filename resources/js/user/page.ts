import type { RouteLocationNormalized } from 'vue-router'
import { reactive } from 'vue'
import http from './http'
import { syncSessionFromPageProps } from './session'

export interface SpaPage {
    component: string
    props: Record<string, any>
    url: string
    version: string | null
    loading: boolean
    loaded: boolean
    error: string | null
    status: number | null
}

export class SpaPageRedirect extends Error {
    constructor(public readonly location: string) {
        super('SPA page redirect required')
        this.name = 'SpaPageRedirect'
    }
}

export const page = reactive<SpaPage>({
    component: '',
    props: {},
    url: '',
    version: null,
    loading: false,
    loaded: false,
    error: null,
    status: null,
})

let latestRouteLoad = 0

function replaceProps(nextProps: Record<string, any>): void {
    page.props = nextProps ?? {}
    syncSessionFromPageProps(page.props)
}

export function setPagePayload(payload: any, fallbackUrl = ''): void {
    const inertiaPayload = payload?.component && payload?.props ? payload : null
    const rawProps = inertiaPayload
        ? inertiaPayload.props
        : payload?.data?.props ?? payload?.data ?? payload?.props ?? payload ?? {}

    page.component = inertiaPayload?.component ?? page.component
    page.url = inertiaPayload?.url ?? fallbackUrl
    page.version = inertiaPayload?.version ?? page.version
    page.error = null
    page.status = null
    page.loaded = true
    replaceProps(rawProps)
}

export function mergePageProps(props: Record<string, any>): void {
    replaceProps({ ...page.props, ...props })
}

export function setPageToast(toast: any): void {
    if (!toast) return

    const normalized = typeof toast === 'string'
        ? { message: toast, type: 'success' }
        : toast

    mergePageProps({
        flash: {
            ...(page.props.flash ?? {}),
            toast: normalized,
        },
    })
}

export function setPageError(status: number | null, message?: string | null): void {
    page.status = status
    page.error = message
        ?? (status === 503 ? 'The service is temporarily unavailable.' : 'Unable to load this page.')
    page.loading = false
    page.loaded = true
}

function pageDataUrl(route: RouteLocationNormalized): string | null {
    const source = route.meta.dataUrl
    return typeof source === 'string' && source.length > 0 ? source : null
}

export async function loadRoutePage(route: RouteLocationNormalized): Promise<boolean> {
    const loadId = ++latestRouteLoad
    const url = pageDataUrl(route)

    if (!url) {
        page.component = String(route.name ?? '')
        page.url = route.fullPath
        page.props = {}
        page.loaded = true
        page.loading = false
        page.error = null
        page.status = null
        return true
    }

    page.loading = true
    page.error = null
    page.status = null

    const headers: Record<string, string> = {
        'X-User-Spa': '1',
        // This deliberately remains a browser-navigation Accept header. Some
        // controllers expose a different API payload when expectsJson() is true.
        Accept: 'text/html, application/xhtml+xml',
    }

    try {
        try {
            const response = await http.get(url, {
                params: route.query,
                headers,
                _spaSkipUnauthorized: true,
            } as any)

            if (loadId !== latestRouteLoad) return false
            setPagePayload(response.data, route.fullPath)
            return true
        } catch (error: any) {
            if (loadId !== latestRouteLoad) return false
            if (error?.response?.status !== 409) throw error

            const responseHeaders = error.response.headers
            const spaLocation = responseHeaders?.get?.('X-User-Spa-Location')
                ?? responseHeaders?.['x-user-spa-location']
                ?? error.response.data?.redirect

            if (typeof spaLocation === 'string' && spaLocation !== '') {
                throw new SpaPageRedirect(spaLocation)
            }

            throw error
        }
    } catch (error: any) {
        if (loadId !== latestRouteLoad) return false
        if (error instanceof SpaPageRedirect) throw error

        setPageError(error?.response?.status ?? null, error?.response?.data?.message)
        throw error
    } finally {
        if (loadId === latestRouteLoad) page.loading = false
    }
}
