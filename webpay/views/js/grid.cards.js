$(document).ready(function () {
    const gridId = "oneclick_card_list";
    const Grid = globalThis.prestashop.component.Grid;
    const GridExtensions = globalThis.prestashop.component.GridExtensions;

    const cardsGrid = new Grid(gridId);
    cardsGrid.addExtension(new GridExtensions.FiltersResetExtension());
    cardsGrid.addExtension(new GridExtensions.SortingExtension());
    cardsGrid.addExtension(new GridExtensions.SubmitRowActionExtension());
    cardsGrid.addExtension(new GridExtensions.LinkRowActionExtension());
});
