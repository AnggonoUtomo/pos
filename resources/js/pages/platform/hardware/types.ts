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

export interface LabelSpecification {
    width_mm: number;
    height_mm: number;
    dpi: number;
    orientation: 'portrait' | 'landscape';
}

export interface PrinterProfile extends LabelSpecification {
    id: string;
    name: string;
    printer_name: string;
    gateway_host: string;
    is_active: boolean;
    version: number;
    deleted_at: string | null;
}

export interface CatalogLabel {
    item_id: string;
    unit_id: string;
    name: string;
    sku: string;
    unit_name: string;
    barcode: string;
}

export interface LabelSnapshot {
    item_id?: string;
    unit_id?: string;
    name: string;
    sku: string;
    unit_name: string;
    barcode: string;
}

export type JobStatus = 'queued' | 'sending' | 'submitted' | 'failed_before_send' | 'uncertain';

export interface LabelJob {
    id: string;
    printer_id: string;
    created_by: number;
    kind: 'product_label' | 'test_label';
    status: JobStatus;
    snapshot: LabelSnapshot;
    printer: PrinterProfile;
    copies: number;
    original_job_id: string | null;
    reason: string | null;
    created_at: string;
    expires_at: string | null;
}

export interface GatewayPrinter {
    name: string;
    driver: string;
}
