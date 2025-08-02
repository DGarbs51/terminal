import type { AxiosStatic } from 'axios';
import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;

    interface Window {
        axios: AxiosStatic;
    }
}
