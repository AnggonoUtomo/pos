<?php

namespace App\Modules\Platform\AuditLog\Application\Services;

use JsonException;

final class AuditLogProjection
{
    public const MODULES = [
        'identity' => 'Identitas & Akses', 'inventory.units' => 'Satuan', 'inventory.items' => 'Barang',
        'inventory.warehouses' => 'Gudang', 'inventory.stock' => 'Stok', 'inventory.transfers' => 'Transfer gudang',
        'sales.customers' => 'Pelanggan', 'sales.customer_levels' => 'Level pelanggan', 'sales.pos' => 'Penjualan POS',
        'sales.pos.held_carts' => 'Keranjang tertahan', 'sales.pos.shifts' => 'Shift kasir', 'sales.returns' => 'Retur penjualan',
        'purchasing.suppliers' => 'Supplier', 'purchasing.purchase-orders' => 'Pembelian', 'purchasing.returns' => 'Retur pembelian',
        'finance.payments' => 'Pembayaran', 'finance.payment_accounts' => 'Kas dan bank', 'finance.payment_methods' => 'Metode pembayaran',
        'finance.cashbook' => 'Buku Kas', 'platform.company-settings' => 'Pengaturan toko', 'platform.operational-settings' => 'Operasional',
    ];

    public const FIELDS = [
        'code' => 'Kode', 'sku' => 'SKU', 'name' => 'Nama', 'symbol' => 'Simbol', 'number' => 'Nomor dokumen',
        'is_active' => 'Aktif', 'status' => 'Status', 'version' => 'Versi', 'deleted_at' => 'Waktu dihapus',
        'quantity' => 'Kuantitas', 'base_quantity' => 'Kuantitas dasar', 'unit_cost' => 'Harga pokok', 'total_value' => 'Nilai total',
        'total' => 'Total', 'direction' => 'Arah', 'outcome' => 'Hasil', 'review_status' => 'Status review', 'kind' => 'Jenis',
        'roles' => 'Role', 'permissions' => 'Permission', 'guard_name' => 'Guard',
        'warehouse_id' => 'ID gudang', 'source_warehouse_id' => 'ID gudang asal', 'destination_warehouse_id' => 'ID gudang tujuan',
        'supplier_id' => 'ID supplier', 'customer_id' => 'ID pelanggan', 'base_unit_id' => 'ID satuan dasar', 'level_id' => 'ID level',
        'stock_receipt_id' => 'ID penerimaan stok', 'stock_issue_id' => 'ID pengeluaran stok', 'invoice_id' => 'ID invoice',
        'payment_disbursement_id' => 'ID pembayaran', 'posted_at' => 'Waktu posting', 'paid_at' => 'Waktu pelunasan',
        'account_id' => 'ID akun kas', 'source_id' => 'ID sumber', 'source_type' => 'Jenis sumber',
        'receipt_paper' => 'Format struk', 'backup_enabled' => 'Backup terjadwal', 'backup_time' => 'Jam backup',
        'table_per_page' => 'Baris per halaman',
    ];

    public static function key(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^[a-z][a-z0-9_.-]{0,99}$/D', $value) ? $value : null;
    }

    public static function eventName(?string $event): string
    {
        $last = $event === null ? '' : substr(strrchr('.'.$event, '.'), 1);

        return match ($last) {
            'created' => 'Ditambahkan', 'updated' => 'Diubah', 'deleted' => 'Dihapus', 'restored' => 'Dipulihkan',
            'posted' => 'Diposting', 'paid' => 'Dilunasi', 'received' => 'Diterima', 'issued' => 'Dikeluarkan',
            'adjusted' => 'Koreksi stok', 'disbursed' => 'Pembayaran keluar', 'refunded' => 'Uang dikembalikan',
            'refund_settled' => 'Retur diselesaikan', 'supplier_refund_received' => 'Uang supplier diterima',
            'succeeded' => 'Berhasil', 'failed' => 'Gagal', 'opened' => 'Dibuka', 'closed' => 'Ditutup',
            default => 'Aktivitas lain',
        };
    }

    private function decode(?string $json): array
    {
        if ($json === null || strlen($json) > 262144) {
            return [];
        }
        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);

            return is_array($data) ? $data : [];
        } catch (JsonException) {
            return [];
        }
    }

    private function fields(mixed $data): ?array
    {
        if (! is_array($data)) {
            return null;
        }
        $result = [];
        foreach (self::FIELDS as $field => $label) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            if (in_array($field, ['roles', 'permissions'], true) && is_array($value) && array_is_list($value)) {
                $result[$field] = array_values(array_slice(array_filter($value, fn ($entry) => is_string($entry)
                    && preg_match('/^[a-zA-Z0-9_.-]{1,100}$/D', $entry)), 0, 100));
            } elseif ($value === null || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
                $result[$field] = is_bool($value) || $value === null ? $value : mb_substr((string) $value, 0, 250);
            }
        }

        return $result === [] ? null : $result;
    }

    public function changes(?string $attributes, ?string $properties): array
    {
        $changes = $this->decode($attributes);
        $metadata = $this->decode($properties);
        // v5 package changes and manual audit properties have separate storage.
        $before = $changes['old'] ?? $metadata['old'] ?? $metadata['before'] ?? null;
        $after = $changes['attributes'] ?? $metadata['attributes'] ?? $metadata['after'] ?? $metadata['settings'] ?? $metadata;

        return ['before' => $this->fields($before), 'after' => $this->fields($after)];
    }
}
