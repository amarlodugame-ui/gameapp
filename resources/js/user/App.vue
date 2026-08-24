<script setup lang="ts">
import { RouterView } from 'vue-router'
import { page, setPageError } from './page'
import { router } from './inertia'
import { loadSession, session } from './session'

const retry = async () => {
    try {
        await loadSession(true)
    } catch (error: any) {
        setPageError(error?.response?.status ?? null, error?.response?.data?.message)
        return
    }

    const current = router.currentRoute.value

    if (current.meta.auth && !session.authenticated) {
        await router.replace({ path: '/login', query: { redirect: current.fullPath } })
        return
    }

    if (current.meta.guest && session.authenticated) {
        await router.replace('/games/wingo')
        return
    }

    await router.reload()
}
const goToLogin = () => router.replace('/login')
</script>

<template>
    <div v-if="!page.loaded" class="spa-bootstrap" aria-live="polite">
        <div class="spa-spinner" aria-hidden="true"></div>
        <span>Loading…</span>
    </div>

    <main v-else-if="page.error" class="spa-status-page">
        <div>
            <div class="spa-status-code">{{ page.status ?? '!' }}</div>
            <h1>{{ page.error }}</h1>
            <button v-if="page.status === 401" type="button" @click="goToLogin">Log in</button>
            <button v-else type="button" @click="retry">Try again</button>
        </div>
    </main>

    <RouterView v-else v-slot="{ Component, route }">
        <component :is="Component" :key="route.path" v-bind="page.props" />
    </RouterView>

    <div v-if="page.loading && page.loaded" class="spa-route-progress" aria-hidden="true"></div>
</template>

<style>
.spa-bootstrap,
.spa-status-page {
    min-height: 100vh;
    display: grid;
    place-items: center;
    padding: 24px;
    text-align: center;
    color: #333;
    background: #f7f8fa;
}

.spa-bootstrap {
    gap: 10px;
    align-content: center;
    font: 14px/1.4 Arial, sans-serif;
}

.spa-spinner {
    width: 28px;
    height: 28px;
    margin: 0 auto;
    border: 3px solid #d8e7ff;
    border-top-color: #1677ff;
    border-radius: 50%;
    animation: spa-spin 0.8s linear infinite;
}

.spa-status-page > div {
    width: min(100%, 360px);
    padding: 32px 20px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 8px 30px rgb(0 0 0 / 8%);
}

.spa-status-page h1 {
    margin: 8px 0 20px;
    font: 600 18px/1.4 Arial, sans-serif;
}

.spa-status-page button {
    min-width: 120px;
    padding: 10px 18px;
    border: 0;
    border-radius: 999px;
    color: #fff;
    background: #1677ff;
    cursor: pointer;
}

.spa-status-code {
    color: #1677ff;
    font: 700 34px/1 Arial, sans-serif;
}

.spa-route-progress {
    position: fixed;
    z-index: 10000;
    top: 0;
    left: 0;
    width: 38%;
    height: 2px;
    background: #1677ff;
    animation: spa-progress 1s ease-in-out infinite;
}

@keyframes spa-spin {
    to { transform: rotate(360deg); }
}

@keyframes spa-progress {
    from { transform: translateX(-110%); }
    to { transform: translateX(300%); }
}
</style>
