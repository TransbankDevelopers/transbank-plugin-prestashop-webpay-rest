import Grid from "@PSJs/components/grid/grid";
import FiltersResetExtension from "@PSJs/components/grid/extension/filters-reset-extension";

const { $ } = window;

$(() => {
  const grid = new Grid("transactions");
  grid.addExtension(new FiltersResetExtension());
});
