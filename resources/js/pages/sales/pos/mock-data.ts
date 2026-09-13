import { type CustomerLevelOption, type PaymentLine, type PosItem, type WarehouseOption } from './types';

export const warehouses: WarehouseOption[] = [
    { id: 'wh-main', name: 'Gudang Utama' },
    { id: 'wh-store', name: 'Gudang Toko' },
    { id: 'wh-online', name: 'Gudang Online' },
];

export const customerLevels: CustomerLevelOption[] = [
    { id: 'retail', name: 'Retail', priceModifier: 1 },
    { id: 'member', name: 'Member', priceModifier: 0.96 },
    { id: 'grosir', name: 'Grosir', priceModifier: 0.9 },
];

export const catalogItems: PosItem[] = [
    {
        id: 'itm-rice-5kg',
        sku: 'BRG-1001',
        name: 'Beras Ramos 5 kg',
        category: 'Sembako',
        stock: 84,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Pcs', ratio: 1, price: 76000 },
            { code: 'karton', label: 'Karton', ratio: 6, price: 444000 },
        ],
    },
    {
        id: 'itm-oil-1l',
        sku: 'BRG-1002',
        name: 'Minyak Goreng 1 L',
        category: 'Sembako',
        stock: 126,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Pcs', ratio: 1, price: 18500 },
            { code: 'dus', label: 'Dus', ratio: 12, price: 213000 },
        ],
    },
    {
        id: 'itm-coffee',
        sku: 'BRG-2101',
        name: 'Kopi Robusta 250 g',
        category: 'Minuman',
        stock: 38,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Pcs', ratio: 1, price: 32500 },
            { code: 'pack', label: 'Pack', ratio: 4, price: 124000 },
        ],
    },
    {
        id: 'itm-detergent',
        sku: 'BRG-3104',
        name: 'Deterjen Bubuk 800 g',
        category: 'Rumah Tangga',
        stock: 57,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Pcs', ratio: 1, price: 21800 },
            { code: 'lusin', label: 'Lusin', ratio: 12, price: 249000 },
        ],
    },
    {
        id: 'itm-milk',
        sku: 'BRG-2207',
        name: 'Susu UHT Cokelat 1 L',
        category: 'Minuman',
        stock: 64,
        baseUnit: 'pcs',
        units: [
            { code: 'pcs', label: 'Pcs', ratio: 1, price: 20500 },
            { code: 'dus', label: 'Dus', ratio: 12, price: 238000 },
        ],
    },
];

export const initialPayments: PaymentLine[] = [
    { id: 'cash', method: 'Tunai', amount: 0 },
    { id: 'debit', method: 'Debit', amount: 0 },
    { id: 'transfer', method: 'Transfer', amount: 0 },
];
