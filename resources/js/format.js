const prices = new Intl.NumberFormat('sr-RS', {
    style: 'currency',
    currency: 'RSD',
    maximumFractionDigits: 0,
});

/** 1390 => "1.390 RSD" */
export const formatPrice = (amount) => prices.format(amount);

/** [{name: 'A'}, {name: 'B'}] => "A, B" */
export const formatAuthors = (authors = []) => authors.map((a) => a.name).join(', ');
