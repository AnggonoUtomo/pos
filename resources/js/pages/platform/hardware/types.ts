export interface ScannedIdentity {
    item_id: string;
    sku: string;
    name: string;
    unit_id: string;
    unit_name: string;
    barcode: string | null;
}

export interface ScanResult {
    id: number;
    code: string;
    status: 'found' | 'error';
    message: string;
    identity?: ScannedIdentity;
}
