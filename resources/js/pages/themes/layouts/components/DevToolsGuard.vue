<script setup>
import { onMounted, onBeforeUnmount } from 'vue'

let interval = null
let replaced = false
let originalHtml = ''
let originalBodyOverflow = ''
let originalTitle = ''

const getAppRoot = () => {
  return document.getElementById('app')
}

const renderBlockedScreen = () => {
  return `
    <div id="devtools-blocked-screen" style=" min-height: 100vh; width: 100%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; background: #ffffff; color: #000000; text-align: center; padding: 24px; box-sizing: border-box;">dev by Awshan</div>
  `
}

const replaceApp = () => {
  const app = getAppRoot()
  if (!app || replaced) return

  originalHtml = app.innerHTML
  originalBodyOverflow = document.body.style.overflow
  originalTitle = document.title

  app.innerHTML = renderBlockedScreen()
  document.body.style.overflow = 'hidden'
  document.title = 'dev by Awshan'

  replaced = true
}

const restoreApp = () => {
  const app = getAppRoot()
  if (!app || !replaced) return

  app.innerHTML = originalHtml
  document.body.style.overflow = originalBodyOverflow
  document.title = originalTitle

  replaced = false
}

const detectDevTools = () => {
  const widthThreshold = 160
  const heightThreshold = 160

  const widthDiff = window.outerWidth - window.innerWidth
  const heightDiff = window.outerHeight - window.innerHeight

  const openedBySize = widthDiff > widthThreshold || heightDiff > heightThreshold

  let openedByDebugger = false
  const start = performance.now()
  debugger
  const end = performance.now()

  if (end - start > 100) {
    openedByDebugger = true
  }

  const isOpen = openedBySize || openedByDebugger

  if (isOpen) {
    replaceApp()
  } else {
    restoreApp()
  }
}

const onKeyDown = (e) => {
  const key = e.key.toUpperCase()

  if (
    key === 'F12' ||
    (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(key)) ||
    (e.ctrlKey && key === 'U')
  ) {
    e.preventDefault()
    e.stopPropagation()
  }
}

const onContextMenu = (e) => {
  e.preventDefault()
}

onMounted(() => {
  detectDevTools()
  interval = setInterval(detectDevTools, 800)

  window.addEventListener('resize', detectDevTools)
  window.addEventListener('keydown', onKeyDown)
  window.addEventListener('contextmenu', onContextMenu)
})

onBeforeUnmount(() => {
  if (interval) clearInterval(interval)

  window.removeEventListener('resize', detectDevTools)
  window.removeEventListener('keydown', onKeyDown)
  window.removeEventListener('contextmenu', onContextMenu)

  restoreApp()
})
</script>

<template>
  <span style="display: none;"></span>
</template>