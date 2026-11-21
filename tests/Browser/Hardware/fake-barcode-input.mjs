export class FakeBarcodeInput {
    constructor(page) {
        this.page = page;
    }

    async emit(code, { enter = true } = {}) {
        const input = this.page.locator('#hardware-scan-code');
        await input.focus();
        await this.page.keyboard.type(code);
        if (enter) await this.page.keyboard.press('Enter');
    }
}
