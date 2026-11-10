import { expect, test } from '@playwright/test';

/** Prijava kroz formular, kao što to radi korisnik. */
async function prijava(page) {
    await page.goto('/prijava');
    await page.getByLabel('Email adresa').first().fill('petar@knjizara.test');
    await page.getByLabel('Lozinka', { exact: true }).fill('password');
    await page.getByRole('button', { name: 'Prijavi se' }).click();
    await expect(page).toHaveURL('/nalog');
}

test('kupac poručuje knjigu i vidi porudžbinu na nalogu', async ({ page }) => {
    const greske = [];
    page.on('console', (m) => m.type() === 'error' && greske.push(m.text()));

    await prijava(page);
    await expect(page.getByText('Još nemate porudžbina.')).toBeVisible();

    // Katalog: samo knjige na stanju, prva u korpu
    await page.goto('/katalog?in_stock=1');
    const prva = page.locator('#knjige li').first();
    const naslov = await prva.locator('[data-naslov]').textContent();
    await prva.getByRole('button', { name: 'U korpu' }).click();
    await expect(page.locator('#broj-u-korpi')).toHaveText('1');

    // Korpa i porudžbina
    await page.getByRole('button', { name: /Korpa/ }).click();
    await expect(page.locator('#korpa')).toContainText(naslov);
    await page.getByRole('button', { name: 'Poruči' }).click();
    const potvrda = page.locator('#korpa [data-porudzbina]');
    await expect(potvrda).toHaveText(/^Porudžbina \d+ je primljena\./);
    const broj = (await potvrda.textContent()).match(/\d+/)[0];
    await expect(page.locator('#broj-u-korpi')).toHaveText('0');

    // Nalog: nova porudžbina, na čekanju, sa dugmetom za otkazivanje
    await page.goto('/nalog');
    const porudzbina = page.locator(`#porudzbine [data-id="${broj}"]`);
    await expect(porudzbina).toContainText('na čekanju');
    await expect(porudzbina.getByRole('button', { name: 'Otkaži' })).toBeVisible();

    // Ni jedna greška u konzoli, ni kršenje politike sadržaja (CSP)
    expect(greske).toEqual([]);
});

test('kupac ne vidi stranicu za upravljanje porudžbinama', async ({ page }) => {
    await prijava(page);

    const odgovor = await page.goto('/upravljanje/porudzbine');
    expect(odgovor.status()).toBe(403);
});
