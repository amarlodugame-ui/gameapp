import axios, { type AxiosRequestConfig, type Method } from 'axios'
import {
    computed,
    defineComponent,
    h,
    onBeforeUnmount,
    reactive,
    ref,
    watchEffect,
    type PropType,
} from 'vue'
import { isNavigationFailure, RouterLink, type RouteLocationRaw } from 'vue-router'
import http from './http'
import { emitNavigation, onNavigation } from './navigation'
import { loadRoutePage, mergePageProps, page, setPagePayload, setPageToast } from './page'
import {
    preserveNextScroll,
    resolveSpaLocation,
    spaPathForLegacyName,
    spaRouter,
} from './router'
import { clearSession, loadSession, session } from './session'

interface VisitOptions {
    data?: Record<string, any>
    replace?: boolean
    preserveScroll?: boolean
    preserveState?: boolean
    only?: string[]
    headers?: Record<string, string>
    onBefore?: () => boolean | void
    onStart?: () => void
    onProgress?: (progress: unknown) => void
    onSuccess?: (page: typeof import('./page').page) => void
    onError?: (errors: Record<string, string>) => void
    onFinish?: () => void
}

function normalizeErrors(errors: Record<string, any> | undefined): Record<string, string> {
    return Object.fromEntries(
        Object.entries(errors ?? {}).map(([key, value]) => [
            key,
            Array.isArray(value) ? String(value[0] ?? '') : String(value ?? ''),
        ]),
    )
}

function inputUrl(input: string | RouteLocationRaw): string | null {
    return typeof input === 'string' ? input : null
}

function isLogoutUrl(input: string | RouteLocationRaw): boolean {
    const value = inputUrl(input)
    if (!value) return false

    try {
        return new URL(value, window.location.origin).pathname.toLowerCase() === '/user/logout'
    } catch {
        return false
    }
}

function withQuery(location: RouteLocationRaw, data: Record<string, any>): RouteLocationRaw {
    if (typeof location !== 'string') return { ...location, query: { ...(location as any).query, ...data } }

    const parsed = new URL(location, window.location.origin)
    Object.entries(data).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') {
            parsed.searchParams.delete(key)
        } else if (Array.isArray(value)) {
            parsed.searchParams.delete(key)
            value.forEach((item) => parsed.searchParams.append(key, String(item)))
        } else {
            parsed.searchParams.set(key, String(value))
        }
    })

    return `${parsed.pathname}${parsed.search}`
}

async function refreshCurrentPage(): Promise<boolean> {
    return loadRoutePage(spaRouter.currentRoute.value)
}

function currentLegacyPageUrl(): string {
    const current = spaRouter.currentRoute.value
    const dataUrl = typeof current.meta.dataUrl === 'string' ? current.meta.dataUrl : '/user/login'
    const url = new URL(dataUrl, window.location.origin)

    Object.entries(current.query).forEach(([key, value]) => {
        if (Array.isArray(value)) {
            value.forEach((item) => {
                if (item != null) url.searchParams.append(key, String(item))
            })
        } else if (value != null) {
            url.searchParams.set(key, String(value))
        }
    })

    return `${url.pathname}${url.search}`
}

async function navigate(
    input: string | RouteLocationRaw,
    data: Record<string, any> = {},
    options: VisitOptions = {},
): Promise<boolean> {
    if (options.onBefore?.() === false) return false
    options.onStart?.()

    if (isLogoutUrl(input)) {
        await logout(options)
        return true
    }

    const resolved = resolveSpaLocation(input)
    if (!resolved) {
        if (typeof input === 'string') window.location.assign(input)
        return false
    }

    const destination = withQuery(resolved, data)
    const destinationRoute = spaRouter.resolve(destination)

    if (options.preserveScroll) preserveNextScroll()

    try {
        let navigationResult

        if (destinationRoute.fullPath === spaRouter.currentRoute.value.fullPath) {
            emitNavigation('start', { to: destinationRoute })
            const applied = await refreshCurrentPage()
            if (!applied) return false
            emitNavigation('success', { to: destinationRoute })
            emitNavigation('finish', { to: destinationRoute })
        } else if (options.replace) {
            navigationResult = await spaRouter.replace(destination)
        } else {
            navigationResult = await spaRouter.push(destination)
        }

        if (navigationResult && isNavigationFailure(navigationResult)) return false

        if (page.error) {
            throw Object.assign(new Error(page.error), { status: page.status })
        }

        options.onSuccess?.(page)
        return true
    } catch (error: any) {
        const errors = normalizeErrors(error?.response?.data?.errors)
        options.onError?.(errors)
        emitNavigation('error', error)
        return false
    } finally {
        options.onFinish?.()
    }
}

function responseToast(payload: any): any {
    return payload?.toast
        ?? payload?.data?.toast
        ?? (payload?.message ? { message: payload.message, type: payload.type ?? 'success', icon: payload.icon ?? null } : null)
}

async function applyActionResponse(payload: any, actionUrl: string): Promise<boolean> {
    if (payload?.component && payload?.props) {
        setPagePayload(payload, spaRouter.currentRoute.value.fullPath)
    }

    const redirect = payload?.redirect ?? payload?.data?.redirect
    const target = redirect ? resolveSpaLocation(redirect) : null
    const actionPath = new URL(actionUrl, window.location.origin).pathname.toLowerCase()
    const isAuthAction = actionPath === '/user/login' || actionPath === '/user/register'

    if (isAuthAction && target) {
        const targetRoute = spaRouter.resolve(target)
        if (targetRoute.meta.auth) await loadSession(true)
    }

    if (target) {
        const targetRoute = spaRouter.resolve(target)
        if (targetRoute.fullPath === spaRouter.currentRoute.value.fullPath) {
            if (!await refreshCurrentPage()) return false
        } else {
            const navigationResult = await spaRouter.replace(target)
            if (navigationResult && isNavigationFailure(navigationResult)) return false
        }
    } else if (!payload?.component) {
        if (!await refreshCurrentPage()) return false
    }

    if (page.error) return false
    setPageToast(responseToast(payload))
    return true
}

async function action(
    method: Method,
    url: string,
    data: Record<string, any> = {},
    options: VisitOptions = {},
): Promise<void> {
    if (options.onBefore?.() === false) return
    options.onStart?.()
    emitNavigation('start', { method, url })

    try {
        const config: AxiosRequestConfig = {
            method,
            url,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-User-Spa': '1',
                'X-User-Spa-Page-Url': currentLegacyPageUrl(),
                ...options.headers,
            },
            _spaSkipUnauthorized: true,
        } as any

        if (method.toLowerCase() === 'get') config.params = data
        else config.data = data

        const response = await http.request(config)
        const applied = await applyActionResponse(response.data, url)
        if (!applied) {
            throw Object.assign(
                new Error(page.error ?? 'Action navigation was superseded.'),
                { _spaNavigationFailed: true, status: page.status },
            )
        }
        options.onSuccess?.(page)
        emitNavigation('success', response)
    } catch (error: any) {
        const errors = normalizeErrors(error?.response?.data?.errors)
        const errorPayload = error?.response?.data
        if (Object.keys(errors).length > 0) mergePageProps({ errors })

        if (error?.response?.status === 401) {
            try {
                await loadSession(true)
            } catch {
                clearSession()
            }

            if (!session.authenticated) {
                await spaRouter.replace({
                    path: '/login',
                    query: { redirect: spaRouter.currentRoute.value.fullPath },
                })
            }
        }

        if (errorPayload?.authenticated === false && errorPayload?.redirect) {
            clearSession(errorPayload.csrf_token)
            const target = resolveSpaLocation(errorPayload.redirect) ?? '/login'
            await spaRouter.replace(target)
        }

        if (errorPayload?.message) {
            setPageToast({
                message: errorPayload.message,
                type: 'error',
                icon: 'cross',
            })
        }
        options.onError?.(errors)
        emitNavigation(error?.response || error?._spaNavigationFailed ? 'error' : 'invalid', error)
    } finally {
        options.onFinish?.()
        emitNavigation('finish', { method, url })
    }
}

async function logout(options: VisitOptions = {}): Promise<void> {
    emitNavigation('start', { method: 'post', url: '/user/spa/logout' })

    try {
        const { data } = await http.post('/user/spa/logout', {}, {
            headers: {
                'X-User-Spa': '1',
                'X-User-Spa-Page-Url': currentLegacyPageUrl(),
            },
        })
        const freshCsrfToken = data?.csrf_token ?? data?.data?.csrf_token
        clearSession(freshCsrfToken)
        await spaRouter.replace('/login')
        setPageToast(responseToast(data))
        options.onSuccess?.(page)
    } catch (error: any) {
        options.onError?.(normalizeErrors(error?.response?.data?.errors))
        emitNavigation('error', error)
    } finally {
        options.onFinish?.()
        emitNavigation('finish', { method: 'post', url: '/user/spa/logout' })
    }
}

export const router = {
    get currentRoute() {
        return spaRouter.currentRoute
    },

    visit(url: string | RouteLocationRaw, options: VisitOptions = {}) {
        return navigate(url, options.data ?? {}, options)
    },

    get(url: string | RouteLocationRaw, data: Record<string, any> = {}, options: VisitOptions = {}) {
        return navigate(url, data, options)
    },

    post(url: string, data: Record<string, any> = {}, options: VisitOptions = {}) {
        return action('post', url, data, options)
    },

    put(url: string, data: Record<string, any> = {}, options: VisitOptions = {}) {
        return action('put', url, data, options)
    },

    patch(url: string, data: Record<string, any> = {}, options: VisitOptions = {}) {
        return action('patch', url, data, options)
    },

    delete(url: string, options: VisitOptions = {}) {
        return action('delete', url, options.data ?? {}, options)
    },

    reload(options: VisitOptions = {}) {
        return navigate(spaRouter.currentRoute.value.fullPath, {}, options)
    },

    push(to: RouteLocationRaw) {
        return spaRouter.push(to)
    },

    replace(to: RouteLocationRaw) {
        return spaRouter.replace(to)
    },

    on: onNavigation,

    isActive(legacyRouteName: string): boolean {
        return spaPathForLegacyName(legacyRouteName) === spaRouter.currentRoute.value.path
    },
}

export function usePage(): typeof page {
    return page
}

export function useForm<T extends Record<string, any>>(initialValues: T) {
    const defaults = structuredClone(initialValues)
    const errors = reactive<Record<string, string>>({})
    const processing = ref(false)
    const progress = ref<unknown>(null)
    const wasSuccessful = ref(false)
    const recentlySuccessful = ref(false)
    let recentlySuccessfulTimer: ReturnType<typeof setTimeout> | null = null
    let transformData: (data: T) => Record<string, any> = (data) => data

    let form: any

    form = reactive({
        ...structuredClone(initialValues),
        errors,
        processing,
        progress,
        wasSuccessful,
        recentlySuccessful,
        success: computed(() => wasSuccessful.value),

        data(): T {
            return Object.fromEntries(
                Object.keys(defaults).map((key) => [key, (form as any)[key]]),
            ) as T
        },

        transform(callback: (data: T) => Record<string, any>) {
            transformData = callback
            return form
        },

        setError(field: string | Record<string, any>, value?: string) {
            const values = typeof field === 'string' ? { [field]: value } : field
            Object.assign(errors, normalizeErrors(values))
            return form
        },

        clearErrors(...fields: string[]) {
            if (fields.length === 0) {
                Object.keys(errors).forEach((key) => delete errors[key])
            } else {
                fields.forEach((key) => delete errors[key])
            }
            return form
        },

        reset(...fields: string[]) {
            const keys = fields.length > 0 ? fields : Object.keys(defaults)
            keys.forEach((key) => {
                ;(form as any)[key] = structuredClone((defaults as any)[key])
            })
            return form
        },

        submit(method: Method, url: string, options: VisitOptions = {}) {
            processing.value = true
            wasSuccessful.value = false
            form.clearErrors()

            const requestOptions: VisitOptions = {
                ...options,
                onSuccess: (currentPage) => {
                    wasSuccessful.value = true
                    recentlySuccessful.value = true
                    if (recentlySuccessfulTimer) clearTimeout(recentlySuccessfulTimer)
                    recentlySuccessfulTimer = setTimeout(() => {
                        recentlySuccessful.value = false
                    }, 2000)
                    options.onSuccess?.(currentPage)
                },
                onError: (responseErrors) => {
                    form.setError(responseErrors)
                    options.onError?.(responseErrors)
                },
                onFinish: () => {
                    processing.value = false
                    progress.value = null
                    options.onFinish?.()
                },
            }

            return action(method, url, transformData(form.data()), requestOptions).catch(() => undefined)
        },

        get(url: string, options: VisitOptions = {}) {
            return form.submit('get', url, options)
        },
        post(url: string, options: VisitOptions = {}) {
            return form.submit('post', url, options)
        },
        put(url: string, options: VisitOptions = {}) {
            return form.submit('put', url, options)
        },
        patch(url: string, options: VisitOptions = {}) {
            return form.submit('patch', url, options)
        },
        delete(url: string, options: VisitOptions = {}) {
            return form.submit('delete', url, options)
        },
    })

    onBeforeUnmount(() => {
        if (recentlySuccessfulTimer) clearTimeout(recentlySuccessfulTimer)
    })

    return form
}

export const Link = defineComponent({
    name: 'SpaLink',
    inheritAttrs: false,
    props: {
        href: { type: [String, Object] as PropType<string | RouteLocationRaw>, required: true },
        method: { type: String, default: 'get' },
        as: { type: String, default: 'a' },
        data: { type: Object as PropType<Record<string, any>>, default: () => ({}) },
        replace: { type: Boolean, default: false },
        preserveScroll: { type: Boolean, default: false },
    },
    setup(props, { attrs, slots }) {
        const destination = computed(() => resolveSpaLocation(props.href))

        return () => {
            if (destination.value && props.method.toLowerCase() === 'get' && props.as === 'a') {
                return h(
                    RouterLink,
                    {
                        ...attrs,
                        to: withQuery(destination.value, props.data),
                        replace: props.replace,
                        onClick: () => {
                            if (props.preserveScroll) preserveNextScroll()
                        },
                    },
                    slots,
                )
            }

            const href = typeof props.href === 'string' ? props.href : undefined
            return h(
                props.as,
                {
                    ...attrs,
                    href: props.as === 'a' ? href : undefined,
                    onClick: (event: MouseEvent) => {
                        event.preventDefault()
                        if (props.method.toLowerCase() === 'get') {
                            void navigate(props.href, props.data, {
                                replace: props.replace,
                                preserveScroll: props.preserveScroll,
                            })
                        } else if (href) {
                            void action(props.method as Method, href, props.data)
                        }
                    },
                },
                slots,
            )
        }
    },
})

export const Head = defineComponent({
    name: 'SpaHead',
    props: {
        title: { type: String, default: '' },
    },
    setup(props) {
        watchEffect(() => {
            if (!props.title) return
            const appName = import.meta.env.VITE_APP_NAME || 'Laravel'
            document.title = `${props.title} - ${appName}`
        })

        return () => null
    },
})

// Kept as a named export for components that previously imported Inertia's axios-adjacent helper.
export { axios, session }
