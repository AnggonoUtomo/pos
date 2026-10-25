import type { ReceiptPaper } from '@/types';

export type Settings = { receipt_paper: ReceiptPaper; backup_enabled: boolean; backup_time: string; version: number };
export type BackupStatus = {
    last_manual: { status: 'succeeded' | 'failed'; file: string | null; finished_at: string } | null;
    scheduler_seen_at: string | null;
    last_run: {
        scheduled_for: string;
        status: 'running' | 'succeeded' | 'failed';
        attempts: number;
        started_at: string;
        finished_at: string | null;
        error_code: string | null;
    } | null;
};
