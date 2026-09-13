import React from 'react'
import { createRoot } from 'react-dom/client'
import { sileo, Toaster } from 'sileo' 

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('sileo-portal')
    if (!root) return

    const position = root.dataset.position ?? 'top-right'

    // Dark toasts to match the arena theme
    const options = {
        fill: "#171717",
        roundness: 16,
        styles: {
            title: "font-medium",
            description: "text-white/75!",
            badge: "bg-white/10!",
            button: "bg-white/10! hover:bg-white/15!",
        },
    }

    createRoot(root).render(
        React.createElement(Toaster, { position, theme: 'dark', options })
    )

    // ── Event listeners ────────────────────────────────────────────────

    window.addEventListener('sileo', ({ detail }) => {
        const { type = 'info', title, description, duration, position, action } = detail ?? {}
        const opts = {
            ...(title       && { title }),
            ...(description && { description }),
            ...(duration    && { duration }),
            ...(position    && { position }),
            ...(action      && { action: { label: action.label, onClick: () => Livewire.dispatch(action.event, ...(action.params ?? [])) } }),
        }
        const map = {
            success: () => sileo.success(opts),
            error:   () => sileo.error(opts),
            warning: () => sileo.warning(opts),
            loading: () => sileo.loading(opts),
            info:    () => sileo.info(opts),
        }
        ;(map[type] ?? map.info)()
    })

    window.addEventListener('sileo.promise', ({ detail }) => {
        const { event, loading = 'Loading…', success = 'Done!', error = 'Failed.' } = detail ?? {}
        const promise = new Promise((resolve, reject) => {
            window.addEventListener(`sileo.resolve.${event}`, resolve, { once: true })
            window.addEventListener(`sileo.reject.${event}`,  reject,  { once: true })
            Livewire.dispatch(event)
        })
        sileo.promise(promise, { loading, success, error })
    })
})