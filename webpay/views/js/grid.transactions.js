// Reference: https://devdocs.prestashop-project.org/1.7/development/components/global-components/#how-to-use

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
