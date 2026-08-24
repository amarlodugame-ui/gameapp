type NavigationEvent = 'start' | 'finish' | 'success' | 'error' | 'invalid'
type NavigationListener = (event?: unknown) => void

const listeners = new Map<NavigationEvent, Set<NavigationListener>>()

export function emitNavigation(event: NavigationEvent, detail?: unknown): void {
    listeners.get(event)?.forEach((listener) => listener(detail))
}

export function onNavigation(event: NavigationEvent, listener: NavigationListener): () => void {
    if (!listeners.has(event)) {
        listeners.set(event, new Set())
    }

    listeners.get(event)!.add(listener)

    return () => listeners.get(event)?.delete(listener)
}

