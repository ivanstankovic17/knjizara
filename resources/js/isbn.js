/**
 * Ista provera kao App\Rules\Isbn13 na serveru (odeljak 8.4):
 * 13 cifara, težine 1 i 3 naizmenično, zbir deljiv sa 10.
 */
export function isValidIsbn13(value) {
    const digits = value.replace(/[-\s]/g, '');
    if (!/^\d{13}$/.test(digits)) {
        return false;
    }
    const sum = [...digits].reduce((s, d, i) => s + Number(d) * (i % 2 === 0 ? 1 : 3), 0);
    return sum % 10 === 0;
}
