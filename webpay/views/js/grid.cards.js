$(document).ready(function () {
    const gridId = "oneclick_card_list";
    const Grid = window.prestashop.component.Grid;
    const GridExtensions = window.prestashop.component.GridExtensions;

    const cardsGrid = new Grid(gridId);
    cardsGrid.addExtension(new GridExtensions.FiltersResetExtension());
    cardsGrid.addExtension(new GridExtensions.SortingExtension());
    cardsGrid.addExtension(new GridExtensions.SubmitRowActionExtension());
    cardsGrid.addExtension(new GridExtensions.LinkRowActionExtension());
});
