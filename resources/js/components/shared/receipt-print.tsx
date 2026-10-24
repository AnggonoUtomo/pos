import type { ReceiptPaper } from '@/types';
import type { ReactNode } from 'react';
import { createPortal } from 'react-dom';
import './receipt-print.css';

export function ReceiptPaperSurface({ paper, children }: { paper: ReceiptPaper; children: ReactNode }) {
    return (
        <div className="receipt-paper" data-paper={paper}>
            {children}
        </div>
    );
}

export function ReceiptPrintPortal({ paper, children }: { paper: ReceiptPaper; children: ReactNode }) {
    if (typeof document === 'undefined') return null;
    return createPortal(
        <section className="pos-receipt-print" data-paper={paper}>
            <style media="print">{`@page { size: ${paper === 'a4' ? 'A4' : 'auto'}; margin: ${paper === 'a4' ? '12mm' : '2mm'}; }`}</style>
            <ReceiptPaperSurface paper={paper}>{children}</ReceiptPaperSurface>
        </section>,
        document.body,
    );
}
