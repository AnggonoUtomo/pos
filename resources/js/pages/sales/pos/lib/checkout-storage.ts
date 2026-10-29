import type { CheckoutAttempt, PostedInvoice } from '../types';

const ulid = /^[0-7][0-9A-HJKMNP-TV-Z]{25}$/i;
const amount = /^\d{1,28}\.\d{2}$/;
const record = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null && !Array.isArray(value);
const text = (value: unknown): value is string => typeof value === 'string';
const id = (value: unknown): value is string => text(value) && ulid.test(value);

export function readAttempt(raw: string | null): CheckoutAttempt | null {
    if (raw === null) return null;
    const data: unknown = JSON.parse(raw);
    if (!record(data) || data.version !== 1 || !text(data.total) || !amount.test(data.total) || !record(data.payload))
        throw new Error('Pemulihan pembayaran tidak valid.');
    const payload = data.payload;
    const lines = payload.lines;
    const payments = payload.payments;
    if (payload.shift_id !== undefined && !id(payload.shift_id)) throw new Error('Shift pemulihan tidak valid.');
    if (
        payload.held_cart !== undefined &&
        (!record(payload.held_cart) ||
            !id(payload.held_cart.id) ||
            !Number.isInteger(payload.held_cart.version) ||
            Number(payload.held_cart.version) < 1 ||
            Number(payload.held_cart.version) > 2147483646)
    )
        throw new Error('Sumber keranjang tertahan pemulihan tidak valid.');
    if (
        payload.pricing !== undefined &&
        (!record(payload.pricing) ||
            !['fixed', 'percent'].includes(String(payload.pricing.discount_type)) ||
            !['discount_value', 'tax_rate'].every(
                (key) =>
                    text(payload.pricing && (payload.pricing as Record<string, unknown>)[key]) &&
                    amount.test((payload.pricing as Record<string, string>)[key]),
            ))
    )
        throw new Error('Snapshot diskon/pajak pemulihan tidak valid.');
    if (
        ![payload.idempotency_key, payload.warehouse_id, payload.customer_id].every(id) ||
        !text(payload.quote_fingerprint) ||
        !/^[a-f0-9]{64}$/.test(payload.quote_fingerprint) ||
        !Array.isArray(lines) ||
        lines.length < 1 ||
        lines.length > 100 ||
        !Array.isArray(payments) ||
        payments.length < 1 ||
        payments.length > 10 ||
        !lines.every(
            (line) => record(line) && id(line.item_id) && id(line.unit_id) && text(line.quantity) && /^\d{1,14}(?:\.\d{1,6})?$/.test(line.quantity),
        ) ||
        !payments.every((row) => record(row) && id(row.method_id) && text(row.amount) && amount.test(row.amount))
    )
        throw new Error('Payload pemulihan tidak valid.');
    if (
        !Array.isArray(data.cart) ||
        data.cart.length !== lines.length ||
        !data.cart.every((line, index) => {
            if (!record(line) || !record(line.item) || !id(line.unitId) || !text(line.quantity)) return false;
            const item = line.item;
            return (
                item.id === lines[index].item_id &&
                line.unitId === lines[index].unit_id &&
                text(item.sku) &&
                text(item.name) &&
                id(item.base_unit_id) &&
                text(item.base_unit_name) &&
                text(item.stock) &&
                Array.isArray(item.units) &&
                item.units.every(
                    (unit) =>
                        record(unit) &&
                        id(unit.id) &&
                        text(unit.code) &&
                        text(unit.name) &&
                        text(unit.ratio) &&
                        (unit.price === null || text(unit.price)) &&
                        (unit.barcode === null || text(unit.barcode)),
                )
            );
        }) ||
        !Array.isArray(data.methods) ||
        data.methods.length > 10 ||
        !data.methods.every(
            (row) => record(row) && id(row.id) && text(row.code) && text(row.name) && ['cash', 'debit', 'transfer'].includes(String(row.type)),
        )
    )
        throw new Error('Snapshot pemulihan tidak valid.');
    return data as CheckoutAttempt;
}

export function isPostedInvoice(value: unknown): value is PostedInvoice {
    if (!record(value) || !id(value.id) || !text(value.number) || !record(value.payments) || !Array.isArray(value.lines) || value.lines.length < 1)
        return false;
    const monetary = ['subtotal', 'discount_amount', 'tax_amount', 'total'];
    const names = [
        'warehouse_id',
        'warehouse_code',
        'warehouse_name',
        'customer_id',
        'customer_code',
        'customer_name',
        'customer_level_name',
        'posted_at',
    ];
    const payments = value.payments;
    return (
        names.every((key) => text(value[key])) &&
        monetary.every((key) => text(value[key]) && amount.test(value[key])) &&
        payments.total === value.total &&
        ['total', 'tendered', 'change'].every((key) => text(payments[key]) && amount.test(payments[key])) &&
        Array.isArray(payments.payments) &&
        payments.payments.length > 0 &&
        payments.payments.every(
            (row) =>
                record(row) &&
                id(row.method_id) &&
                text(row.method_name) &&
                text(row.method_type) &&
                ['tendered_amount', 'applied_amount', 'change_amount'].every((key) => text(row[key]) && amount.test(row[key])),
        ) &&
        value.lines.every(
            (line) =>
                record(line) &&
                id(line.id) &&
                typeof line.line_number === 'number' &&
                ['name', 'sku', 'unit_name'].every((key) => text(line[key])) &&
                ['quantity', 'base_quantity', 'conversion_ratio'].every((key) => text(line[key]) && /^\d{1,14}(?:\.\d{1,6})?$/.test(line[key])) &&
                ['unit_price', 'subtotal', 'discount_amount', 'tax_amount'].every((key) => text(line[key]) && amount.test(line[key])),
        )
    );
}
