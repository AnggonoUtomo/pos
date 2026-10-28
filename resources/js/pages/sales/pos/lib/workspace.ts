import type { CustomerOption, WarehouseOption } from '../types';

export type WorkspaceView = 'classic' | 'visual';
export const workspaceViewKey = 'pos.workspace.view';

export function workspaceView(value: string | null): WorkspaceView {
    return value === 'classic' ? 'classic' : 'visual';
}

export function initialPosContext(warehouses: WarehouseOption[], customers: CustomerOption[]) {
    const main = warehouses.filter((row) => /^gudang utama(?:\s|$)/i.test(row.name.trim()));
    const retail = customers.filter((row) => row.levelName.trim().toLowerCase() === 'eceran');
    return {
        warehouseId: main.length === 1 ? main[0].id : '',
        customerId: retail.length === 1 ? retail[0].id : '',
    };
}
