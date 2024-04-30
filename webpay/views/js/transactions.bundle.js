$(document).ready(function () {
    const $ = window.$;
    const Grid = window.prestashop.component.Grid;
    const {
        FiltersResetExtension,
    } = window.prestashop.component.GridExtensions;

    $(() => {
        const quotesGrid = new Grid("webpay_transactions");
        quotesGrid.addExtension(new FiltersResetExtension());
    });
});
