export function useCurrencyFormatter() {
    const formatCurrency = (amount, locale = 'es-CO', currency = 'COP') => {
        if (isNaN(amount)) {
            return amount;
        }

        const formatter = new Intl.NumberFormat(locale, {
            style: 'decimal',
            currency: currency,
            maximumFractionDigits: 2
        });

        return formatter.format(amount);
    };

    return { formatCurrency };
}