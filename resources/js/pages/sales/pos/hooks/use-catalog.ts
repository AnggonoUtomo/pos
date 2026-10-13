import { useEffect, useState } from 'react';
import { posRequest } from '../lib/request';
import type { CatalogResult } from '../types';

export function useCatalog(warehouseId: string, customerId: string, query: string, page: number, version: number) {
    const [result, setResult] = useState<CatalogResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    useEffect(() => {
        const controller = new AbortController();
        setResult(null);
        setError('');
        if (!warehouseId || !customerId) {
            setLoading(false);
            return () => controller.abort();
        }
        setLoading(true);
        const timer = setTimeout(() => {
            const params = new URLSearchParams({ warehouse_id: warehouseId, customer_id: customerId, search: query, page: String(page) });
            posRequest<CatalogResult>(route('pos.catalog') + '?' + params.toString(), controller.signal)
                .then((data) => {
                    if (!controller.signal.aborted) setResult(data);
                })
                .catch((error: Error) => {
                    if (!controller.signal.aborted) setError(error.message);
                })
                .finally(() => {
                    if (!controller.signal.aborted) setLoading(false);
                });
        }, 250);
        return () => {
            clearTimeout(timer);
            controller.abort();
        };
    }, [warehouseId, customerId, query, page, version]);
    return { result, loading, error };
}
