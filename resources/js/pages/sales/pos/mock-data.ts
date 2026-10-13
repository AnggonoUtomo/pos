import type { CustomerLevelOption, PaymentLine, PosItem, WarehouseOption } from './types';

export const warehouses: WarehouseOption[] = [
    { id: 'wh-main', name: 'Gudang Utama' },
    { id: 'wh-secondary', name: 'Gudang Tambahan' },
];

export const customerLevels: CustomerLevelOption[] = [
    { id: 'retail', name: 'Eceran', priceModifier: 1 },
    { id: 'grosir', name: 'Grosir', priceModifier: 0.9 },
    { id: 'installer', name: 'Installer', priceModifier: 0.95 },
];

export const catalogItems: PosItem[] = [
    {
        id: 'itm-cctv-ip-2mp',
        sku: 'DEMO-CCTV-IP-2MP',
        name: 'Kamera IP 2MP',
        category: 'CCTV',
        stock: 8,
        baseUnit: 'unit',
        units: [
            { code: 'unit', label: 'Unit', ratio: 1, price: 650000 },
            { code: 'box', label: 'Dus', ratio: 4, price: 2600000 },
        ],
    },
    {
        id: 'itm-cctv-nvr-8ch',
        sku: 'DEMO-CCTV-NVR-8CH',
        name: 'NVR 8 Kanal',
        category: 'CCTV',
        stock: 4,
        baseUnit: 'unit',
        units: [
            { code: 'unit', label: 'Unit', ratio: 1, price: 1250000 },
            { code: 'box', label: 'Dus', ratio: 2, price: 2500000 },
        ],
    },
    {
        id: 'itm-cctv-utp-cat6',
        sku: 'DEMO-CCTV-UTP-CAT6',
        name: 'Kabel UTP Cat6',
        category: 'CCTV',
        stock: 305,
        baseUnit: 'm',
        units: [
            { code: 'm', label: 'Meter', ratio: 1, price: 5000 },
            { code: 'roll', label: 'Rol', ratio: 305, price: 1525000 },
        ],
    },
    {
        id: 'itm-elec-nym-3x15',
        sku: 'DEMO-ELEC-NYM-3X15',
        name: 'Kabel NYM 3x1.5 mm2',
        category: 'Kelistrikan',
        stock: 150,
        baseUnit: 'm',
        units: [
            { code: 'm', label: 'Meter', ratio: 1, price: 19000 },
            { code: 'roll', label: 'Rol', ratio: 100, price: 1900000 },
        ],
    },
    {
        id: 'itm-elec-mcb-6a',
        sku: 'DEMO-ELEC-MCB-6A',
        name: 'MCB 1P 6A',
        category: 'Kelistrikan',
        stock: 12,
        baseUnit: 'unit',
        units: [
            { code: 'unit', label: 'Unit', ratio: 1, price: 45000 },
            { code: 'box', label: 'Dus', ratio: 12, price: 540000 },
        ],
    },
    {
        id: 'itm-cctv-rj45',
        sku: 'DEMO-CCTV-RJ45',
        name: 'Konektor RJ45',
        category: 'CCTV',
        stock: 1000,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Buah', ratio: 1, price: 2500 },
            { code: 'pack', label: 'Pak', ratio: 100, price: 250000 },
            { code: 'box', label: 'Dus', ratio: 1000, price: 2500000 },
        ],
    },
    {
        id: 'itm-cctv-adapter-12v',
        sku: 'DEMO-CCTV-ADAPTER-12V',
        name: 'Adaptor CCTV 12V',
        category: 'CCTV',
        stock: 20,
        baseUnit: 'unit',
        units: [
            { code: 'unit', label: 'Unit', ratio: 1, price: 65000 },
            { code: 'box', label: 'Dus', ratio: 10, price: 650000 },
        ],
    },
    {
        id: 'itm-cctv-balun',
        sku: 'DEMO-CCTV-BALUN',
        name: 'Balun Video',
        category: 'CCTV',
        stock: 24,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Buah', ratio: 1, price: 15000 },
            { code: 'pair', label: 'Pasang', ratio: 2, price: 30000 },
        ],
    },
    {
        id: 'itm-cctv-kit',
        sku: 'DEMO-CCTV-KIT',
        name: 'Paket CCTV',
        category: 'CCTV',
        stock: 3,
        baseUnit: 'set',
        units: [{ code: 'set', label: 'Set', ratio: 1, price: 3200000 }],
    },
];

export const initialPayments: PaymentLine[] = [
    { id: 'cash', method: 'Tunai', amount: 0 },
    { id: 'debit', method: 'Debit', amount: 0 },
    { id: 'transfer', method: 'Transfer', amount: 0 },
];
