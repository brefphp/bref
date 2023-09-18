import '../../styles/main.css';
import { useRouter } from 'next/router';
import { useEffect } from 'react';
const redirects = require('../../redirects').redirects;

export default function MyApp({ Component, pageProps }) {
    const router = useRouter();

    useEffect(() => {
        // If a redirect matches the current path, redirect to the new path
        if (redirects[router.asPath]) {
            router.push(redirects[router.asPath]);
        }
    }, []);

    return <Component {...pageProps} />
}
