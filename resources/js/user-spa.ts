import { createApp } from 'vue'
import { ZiggyVue } from 'ziggy-js'
import '../css/app.css'
import '@/echo'
import App from './user/App.vue'
import http, { onAuthLoss, onUnauthorized } from './user/http'
import { clearSession, loadSession, session } from './user/session'
import spaRouter, { resolveSpaLocation } from './user/router'

onUnauthorized(() => {
    void (async () => {
        try {
            await loadSession(true)
        } catch {
            clearSession()
        }

        if (!session.authenticated && spaRouter.currentRoute.value.meta.auth) {
            await spaRouter.replace({
                path: '/login',
                query: { redirect: spaRouter.currentRoute.value.fullPath },
            })
        }
    })()
})

onAuthLoss((payload) => {
    void (async () => {
        clearSession(payload?.csrf_token)
        const target = resolveSpaLocation(payload?.redirect) ?? '/login'
        await spaRouter.replace(target)
    })()
})

createApp(App)
    .use(spaRouter)
    .use(ZiggyVue)
    .mount('#app')

// Make the configured singleton explicit for existing components importing axios directly.
void http
