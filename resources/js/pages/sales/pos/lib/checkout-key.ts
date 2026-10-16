const alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

export function checkoutKey(timestamp = Date.now(), randomness = crypto.getRandomValues(new Uint8Array(10))): string {
    if (!Number.isSafeInteger(timestamp) || timestamp < 0 || timestamp > 281474976710655 || randomness.length !== 10) {
        throw new Error('Kunci checkout tidak valid.');
    }
    let value = BigInt(timestamp);
    for (const byte of randomness) value = (value << 8n) | BigInt(byte);
    let key = '';
    for (let index = 0; index < 26; index++) {
        key = alphabet[Number(value & 31n)] + key;
        value >>= 5n;
    }
    return key;
}
