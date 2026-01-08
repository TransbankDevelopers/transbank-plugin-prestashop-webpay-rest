$(document).ready(function () {
    const gridId = "oneclick_card_list";
    const Grid = window.prestashop.component.Grid;
    const GridExtensions = window.prestashop.component.GridExtensions;

    const cardsGrid = new Grid(gridId);
    cardsGrid.addExtension(new GridExtensions.FiltersResetExtension());
    cardsGrid.addExtension(new GridExtensions.SortingExtension());
    cardsGrid.addExtension(new GridExtensions.SubmitRowActionExtension());
    cardsGrid.addExtension(new GridExtensions.LinkRowActionExtension());

    if (typeof window.webpayDeleteFailedData !== "undefined") {
        const deleteData =
            typeof window.webpayDeleteFailedData === "string"
                ? JSON.parse(window.webpayDeleteFailedData)
                : window.webpayDeleteFailedData;

        $("#forceDeleteForm").attr("action", deleteData.forceDeleteUrl);
        $("#forceDeleteToken").val(deleteData.csrfToken);

        $("#forceDeleteModal").modal("show");

        delete window.webpayDeleteFailedData;
    }
});
