export type UnitOption = {
    code: string;
    label: string;
    ratio: number;
    price: number;
};

export type PosItem = {
    id: string;
    sku: string;
    name: string;
    category: string;
    stock: number;
    baseUnit: string;
    units: UnitOption[];
};

export type CartLine = {
    item: PosItem;
    unitCode: string;
    qty: number;
    discount: number;
};

export type WarehouseOption = {
    id: string;
    name: string;
};

export type CustomerLevelOption = {
    id: string;
    name: string;
    priceModifier: number;
};

export type PaymentLine = {
    id: string;
    method: string;
    amount: number;
};
