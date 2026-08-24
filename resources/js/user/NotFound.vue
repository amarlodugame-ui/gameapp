<script setup lang="ts">
import { router } from './inertia'
import { session } from './session'

const goBack = () => {
    if (window.history.length > 1) {
        window.history.back()
        return
    }

    router.replace(session.authenticated ? '/home' : '/login')
}
</script>

<template>
    <main class="spa-status-page">
        <div>
            <div class="spa-status-code">404</div>
            <h1>Page not found</h1>
            <button type="button" @click="goBack">Go back</button>
        </div>
    </main>
</template>

