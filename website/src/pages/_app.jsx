import '../../styles/main.css';
import { useRouter } from 'next/router';
import { useEffect } from 'react';
import PlausibleProvider from 'next-plausible';
const redirects = require('../../redirects').redirects;

export default function MyApp({ Component, pageProps }) {
    // Custom code to redirect old URLs to new ones
    // This runs client-side to redirect anchor tags
    const router = useRouter();
    useEffect(() => {
        // For the initial page load
        if (redirects[router.asPath]) {
            router.replace(redirects[router.asPath]);
        }
        // For client-side routing
        const onRouteChange = (url) => {
            if (redirects[url]) {
                router.replace(redirects[url]);
            }
        }
        router.events.on('routeChangeStart', onRouteChange)
        return () => { // If the component is unmounted, unsubscribe
            router.events.off('routeChangeStart', onRouteChange)
        }
    }, []);

    return (
        <PlausibleProvider domain="bref.sh" trackOutboundLinks={true}>
            <Component {...pageProps} />
        </PlausibleProvider>
    )
}
