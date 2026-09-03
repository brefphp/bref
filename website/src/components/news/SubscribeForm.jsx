'use client'

import { useEffect, useRef } from 'react'

// Same Mailcoach list as the homepage form (src/components/home/case-studies.jsx).
const MAILCOACH_SUBSCRIBE_URL = 'https://bref.mailcoach.app/subscribe/be83c960-8fee-43fa-abfa-29669e9f433a'

// POST-only subscribe form for the Bref newsletter, used inline on /news and on
// /news/subscribe (the page that moves the old "Serverless PHP news" list to the
// Bref list with an explicit opt-in).
//
// - `tag`: Mailcoach subscriber tag, to measure where subscribers come from.
// - `label`: visible label above the field. Without it the form is a single row
//   (placeholder + button).
// - `onDark`: styles for a dark background (the homepage section), instead of
//   following the site theme.
// - The migration email links to /news/subscribe?email=... : the address is
//   prefilled but nothing is submitted until the reader clicks the button, so
//   email prefetchers (which only GET the link) cannot subscribe anyone.
// - Mailcoach sends its double opt-in email and redirects to the bref.sh
//   result pages below.
const INPUT_CLASS = {
    light: 'text-gray-900 ring-gray-300 placeholder:text-gray-400 focus:ring-blue-500 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-white/50',
    dark: 'bg-white/10 text-white ring-white/10 placeholder:text-white/75 focus:ring-white',
}
const BUTTON_CLASS = {
    light: 'bg-blue-500 hover:bg-blue-400',
    dark: '!bg-gray-500 hover:!bg-gray-400',
}

export default function SubscribeForm({ tag, label, onDark = false, className = 'my-8' }) {
    const variant = onDark ? 'dark' : 'light'
    const emailRef = useRef(null)
    const honeypotRef = useRef(null)

    useEffect(() => {
        const email = new URLSearchParams(window.location.search).get('email')
        if (email && emailRef.current && !emailRef.current.value) {
            // A `+` in the address decodes to a space if the link was not URL-encoded
            emailRef.current.value = email.replace(/ /g, '+')
        }
    }, [])

    const onSubmit = (event) => {
        // Honeypot: humans never see this field, bots fill it in
        if (honeypotRef.current?.value) {
            event.preventDefault()
            return
        }
        // Mailcoach stores unknown form fields as subscriber attributes: leave the honeypot out
        if (honeypotRef.current) honeypotRef.current.disabled = true
    }

    return (
        <form method="POST" action={MAILCOACH_SUBSCRIBE_URL} onSubmit={onSubmit} className={`not-prose ${className}`}>
            <input type="hidden" name="tags" value={tag} />
            <input type="hidden" name="redirect_after_subscription_pending" value="https://bref.sh/news/subscribe/pending" />
            <input type="hidden" name="redirect_after_already_subscribed" value="https://bref.sh/news/subscribe/already" />
            <input type="hidden" name="redirect_after_subscribed" value="https://bref.sh/news/subscribe/done" />

            <div className="hidden" aria-hidden="true">
                <label htmlFor="subscribe-website">Website</label>
                <input ref={honeypotRef} id="subscribe-website" type="text" name="website" tabIndex={-1} autoComplete="off" />
            </div>

            {label && (
                <label htmlFor="subscribe-email" className="block mb-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {label}
                </label>
            )}
            <div className="flex flex-col sm:flex-row gap-2">
                <input
                    ref={emailRef}
                    id="subscribe-email"
                    type="email"
                    name="email"
                    required
                    autoComplete="email"
                    aria-label={label ? undefined : 'Email'}
                    placeholder="you@example.com"
                    className={`min-w-0 flex-auto rounded-md border-0 px-3.5 py-2 shadow-sm ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-sm sm:leading-6 ${INPUT_CLASS[variant]}`}
                />
                <button
                    type="submit"
                    className={`flex-none rounded-md px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500 ${BUTTON_CLASS[variant]}`}
                >
                    Subscribe to the Bref newsletter
                </button>
            </div>
        </form>
    )
}
