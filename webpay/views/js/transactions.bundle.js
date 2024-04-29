(()=>{"use strict";var t={9567:t=>{t.exports=window.jQuery}},e={};function n(o){var i=e[o];if(void 0!==i)return i.exports;var s=e[o]={exports:{}};return t[o](s,s.exports,n),s.exports}n.g=function(){if("object"==typeof globalThis)return globalThis;try{return this||new Function("return this")()}catch(t){if("object"==typeof window)return window}}(),n.r=t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})};var o={};(()=>{n.r(o);
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */const t={deleteCategories:".js-delete-categories-bulk-action",deleteCategoriesModal:t=>`#${t}_grid_delete_categories_modal`,checkedCheckbox:".js-bulk-action-checkbox:checked",deleteCustomers:".js-delete-customers-bulk-action",deleteCustomerModal:t=>`#${t}_grid_delete_customers_modal`,submitDeleteCategories:".js-submit-delete-categories",submitDeleteCustomers:".js-submit-delete-customers",categoriesToDelete:"#delete_categories_categories_to_delete",customersToDelete:"#delete_customers_customers_to_delete",actionSelectAll:".js-bulk-action-select-all",bulkActionCheckbox:".js-bulk-action-checkbox",bulkActionBtn:".js-bulk-actions-btn",openTabsBtn:".js-bulk-action-btn.open_tabs",tableChoiceOptions:"table.table .js-choice-options",choiceOptions:".js-choice-options",modalFormSubmitBtn:".js-bulk-modal-form-submit-btn",submitAction:".js-bulk-action-submit-btn",ajaxAction:".js-bulk-action-ajax-btn",gridSubmitAction:".js-grid-action-submit-btn"},e={categoryDeleteAction:".js-delete-category-row-action",customerDeleteAction:".js-delete-customer-row-action",linkRowAction:".js-link-row-action",linkRowActionClickableFirst:".js-link-row-action[data-clickable-row=1]:first",clickableTd:"td.clickable"},i={showQuery:".js-common_show_query-grid-action",exportQuery:".js-common_export_sql_manager-grid-action",showModalForm:t=>`#${t}_common_show_query_modal_form`,showModalGrid:t=>`#${t}_grid_common_show_query_modal`,modalFormSubmitBtn:".js-bulk-modal-form-submit-btn",submitModalFormBtn:".js-submit-modal-form-btn",bulkInputsBlock:t=>`#${t}`,tokenInput:t=>`input[name="${t}[_token]"]`,ajaxBulkActionConfirmModal:(t,e)=>`${t}-ajax-${e}-confirm-modal`,ajaxBulkActionProgressModal:(t,e)=>`${t}-ajax-${e}-progress-modal`},s=t=>`${t}-grid-confirm-modal`,r=".js-grid-table",a=t=>`#${t}_grid`,c=".js-grid-panel",l=".js-grid-header",d="table.table",h=".header-toolbar",u=".breadcrumb-item",m=".js-reset-search",f=".js-common_refresh_list-grid-action",b=t=>`#${t}_filter_form`,p=".btn-sql-submit",{$:g}=window;class w{constructor(t){this.id=t,this.$container=g(a(this.id))}getId(){return this.id}getContainer(){return this.$container}getHeaderContainer(){return this.$container.closest(c).find(l)}addExtension(t){t.extend(this)}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:v}=window,_=function(t,e){v.post(t).then((()=>window.location.assign(e)))},{$:y}=window;class k{extend(t){t.getContainer().on("click",m,(t=>{_(y(t.currentTarget).data("url"),y(t.currentTarget).data("redirect"))}))}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:C}=window;const E=class{constructor(t){this.selector=".ps-sortable-column",this.columns=C(t).find(this.selector)}attach(){this.columns.on("click",(t=>{const e=C(t.delegateTarget);this.sortByColumn(e,this.getToggledSortDirection(e))}))}sortBy(t,e){if(!this.columns.is(`[data-sort-col-name="${t}"]`))throw new Error(`Cannot sort by "${t}": invalid column`);this.sortByColumn(this.columns,e)}sortByColumn(t,e){window.location.href=this.getUrl(t.data("sortColName"),"desc"===e?"desc":"asc",t.data("sortPrefix"))}getToggledSortDirection(t){return"asc"===t.data("sortDirection")?"desc":"asc"}getUrl(t,e,n){const o=new URL(window.location.href),i=o.searchParams;return n?(i.set(`${n}[orderBy]`,t),i.set(`${n}[sortOrder]`,e)):(i.set("orderBy",t),i.set("sortOrder",e)),o.toString()}};
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  class B{extend(t){const e=t.getContainer().find(d);new E(e).attach()}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:O}=window;class x{extend(t){t.getHeaderContainer().on("click",i.showQuery,(()=>this.onShowSqlQueryClick(t))),t.getHeaderContainer().on("click",i.exportQuery,(()=>this.onExportSqlManagerClick(t)))}onShowSqlQueryClick(t){const e=O(i.showModalForm(t.getId()));this.fillExportForm(e,t);const n=O(i.showModalGrid(t.getId()));n.modal("show"),n.on("click",p,(()=>e.submit()))}onExportSqlManagerClick(t){const e=O(i.showModalForm(t.getId()));this.fillExportForm(e,t),e.submit()}fillExportForm(t,e){const n=e.getContainer().find(r).data("query");t.find('textarea[name="sql"]').val(n),t.find('input[name="name"]').val(this.getNameFromBreadcrumb())}getNameFromBreadcrumb(){const t=O(h).find(u);let e="";return t.each(((t,n)=>{const o=O(n),i=o.find("a").length>0?o.find("a").text():o.text();e.length>0&&(e=e.concat(" > ")),e=e.concat(i)})),e}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  class j{extend(t){t.getHeaderContainer().on("click",f,(()=>{window.location.reload()}))}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:A}=window;class M{extend(t){this.handleBulkActionCheckboxSelect(t),this.handleBulkActionSelectAllCheckbox(t)}handleBulkActionSelectAllCheckbox(e){e.getContainer().on("change",t.actionSelectAll,(n=>{const o=A(n.currentTarget).is(":checked");o?this.enableBulkActionsBtn(e):this.disableBulkActionsBtn(e),e.getContainer().find(t.bulkActionCheckbox).prop("checked",o)}))}handleBulkActionCheckboxSelect(e){e.getContainer().on("change",t.bulkActionCheckbox,(()=>{e.getContainer().find(t.checkedCheckbox).length>0?this.enableBulkActionsBtn(e):this.disableBulkActionsBtn(e)}))}enableBulkActionsBtn(e){e.getContainer().find(t.bulkActionBtn).prop("disabled",!1)}disableBulkActionsBtn(e){e.getContainer().find(t.bulkActionBtn).prop("disabled",!0)}}var T=n(9567),S=Object.defineProperty,L=Object.getOwnPropertySymbols,$=Object.prototype.hasOwnProperty,F=Object.prototype.propertyIsEnumerable,I=(t,e,n)=>e in t?S(t,e,{enumerable:!0,configurable:!0,writable:!0,value:n}):t[e]=n,P=(t,e)=>{for(var n in e||(e={}))$.call(e,n)&&I(t,n,e[n]);if(L)for(var n of L(e))F.call(e,n)&&I(t,n,e[n]);return t};
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  class D{constructor(t){const e=P({id:"confirm-modal",closable:!1},t);this.buildModalContainer(e)}buildModalContainer(t){this.container=document.createElement("div"),this.container.classList.add("modal","fade"),this.container.id=t.id,this.dialog=document.createElement("div"),this.dialog.classList.add("modal-dialog"),t.dialogStyle&&Object.keys(t.dialogStyle).forEach((e=>{this.dialog.style[e]=t.dialogStyle[e]})),this.content=document.createElement("div"),this.content.classList.add("modal-content"),this.message=document.createElement("p"),this.message.classList.add("modal-message"),this.header=document.createElement("div"),this.header.classList.add("modal-header"),t.modalTitle&&(this.title=document.createElement("h4"),this.title.classList.add("modal-title"),this.title.innerHTML=t.modalTitle),this.closeIcon=document.createElement("button"),this.closeIcon.classList.add("close"),this.closeIcon.setAttribute("type","button"),this.closeIcon.dataset.dismiss="modal",this.closeIcon.innerHTML="×",this.body=document.createElement("div"),this.body.classList.add("modal-body","text-left","font-weight-normal"),this.title&&this.header.appendChild(this.title),this.header.appendChild(this.closeIcon),this.content.append(this.header,this.body),this.body.appendChild(this.message),this.dialog.appendChild(this.content),this.container.appendChild(this.dialog)}}class R{constructor(t){const e=P({id:"confirm-modal",closable:!1,dialogStyle:{}},t);this.initContainer(e)}initContainer(t){this.modal||(this.modal=new D(t)),this.$modal=T(this.modal.container);const{id:e,closable:n}=t;this.$modal.modal({backdrop:!!n||"static",keyboard:void 0===n||n,show:!1}),this.$modal.on("hidden.bs.modal",(()=>{const n=document.querySelector(`#${e}`);n&&n.remove(),t.closeCallback&&t.closeCallback()})),document.body.appendChild(this.modal.container)}setTitle(t){this.modal.title||(this.modal.title=document.createElement("h4"),this.modal.title.classList.add("modal-title"),this.modal.closeIcon?this.modal.header.insertBefore(this.modal.title,this.modal.closeIcon):this.modal.header.appendChild(this.modal.title)),this.modal.title.innerHTML=t}render(t){this.modal.message.innerHTML=t}show(){this.$modal.modal("show")}hide(){this.$modal.modal("hide"),this.$modal.on("shown.bs.modal",(()=>{this.$modal.modal("hide"),this.$modal.off("shown.bs.modal")}))}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  function q(t){return void 0===t}var H=Object.defineProperty,z=Object.getOwnPropertySymbols,G=Object.prototype.hasOwnProperty,W=Object.prototype.propertyIsEnumerable,N=(t,e,n)=>e in t?H(t,e,{enumerable:!0,configurable:!0,writable:!0,value:n}):t[e]=n;
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  class Q extends D{constructor(t){super(t)}buildModalContainer(t){super.buildModalContainer(t),this.message.classList.add("confirm-message"),this.message.innerHTML=t.confirmMessage,this.footer=document.createElement("div"),this.footer.classList.add("modal-footer"),this.closeButton=document.createElement("button"),this.closeButton.setAttribute("type","button"),this.closeButton.classList.add("btn","btn-outline-secondary","btn-lg"),this.closeButton.dataset.dismiss="modal",this.closeButton.innerHTML=t.closeButtonLabel,this.confirmButton=document.createElement("button"),this.confirmButton.setAttribute("type","button"),this.confirmButton.classList.add("btn",t.confirmButtonClass,"btn-lg","btn-confirm-submit"),this.confirmButton.dataset.dismiss="modal",this.confirmButton.innerHTML=t.confirmButtonLabel,this.footer.append(this.closeButton,...t.customButtons,this.confirmButton),this.content.append(this.footer)}}class V extends R{constructor(t,e,n){var o;let i;i=q(t.confirmCallback)?q(e)?()=>{console.error("No confirm callback provided for ConfirmModal component.")}:e:t.confirmCallback;super(((t,e)=>{for(var n in e||(e={}))G.call(e,n)&&N(t,n,e[n]);if(z)for(var n of z(e))W.call(e,n)&&N(t,n,e[n]);return t})({id:"confirm-modal",confirmMessage:"Are you sure?",closeButtonLabel:"Close",confirmButtonLabel:"Accept",confirmButtonClass:"btn-primary",customButtons:[],closable:!1,modalTitle:t.confirmTitle,dialogStyle:{},confirmCallback:i,closeCallback:null!=(o=t.closeCallback)?o:n},t))}initContainer(t){this.modal=new Q(t),this.modal.confirmButton.addEventListener("click",t.confirmCallback),super.initContainer(t)}}var U=function(){if("undefined"!=typeof Map)return Map;function t(t,e){var n=-1;return t.some((function(t,o){return t[0]===e&&(n=o,!0)})),n}return function(){function e(){this.__entries__=[]}return Object.defineProperty(e.prototype,"size",{get:function(){return this.__entries__.length},enumerable:!0,configurable:!0}),e.prototype.get=function(e){var n=t(this.__entries__,e),o=this.__entries__[n];return o&&o[1]},e.prototype.set=function(e,n){var o=t(this.__entries__,e);~o?this.__entries__[o][1]=n:this.__entries__.push([e,n])},e.prototype.delete=function(e){var n=this.__entries__,o=t(n,e);~o&&n.splice(o,1)},e.prototype.has=function(e){return!!~t(this.__entries__,e)},e.prototype.clear=function(){this.__entries__.splice(0)},e.prototype.forEach=function(t,e){void 0===e&&(e=null);for(var n=0,o=this.__entries__;n<o.length;n++){var i=o[n];t.call(e,i[1],i[0])}},e}()}(),J="undefined"!=typeof window&&"undefined"!=typeof document&&window.document===document,K=void 0!==n.g&&n.g.Math===Math?n.g:"undefined"!=typeof self&&self.Math===Math?self:"undefined"!=typeof window&&window.Math===Math?window:Function("return this")(),X="function"==typeof requestAnimationFrame?requestAnimationFrame.bind(K):function(t){return setTimeout((function(){return t(Date.now())}),1e3/60)};var Y=["top","right","bottom","left","width","height","size","weight"],Z="undefined"!=typeof MutationObserver,tt=function(){function t(){this.connected_=!1,this.mutationEventsAdded_=!1,this.mutationsObserver_=null,this.observers_=[],this.onTransitionEnd_=this.onTransitionEnd_.bind(this),this.refresh=function(t,e){var n=!1,o=!1,i=0;function s(){n&&(n=!1,t()),o&&a()}function r(){X(s)}function a(){var t=Date.now();if(n){if(t-i<2)return;o=!0}else n=!0,o=!1,setTimeout(r,e);i=t}return a}(this.refresh.bind(this),20)}return t.prototype.addObserver=function(t){~this.observers_.indexOf(t)||this.observers_.push(t),this.connected_||this.connect_()},t.prototype.removeObserver=function(t){var e=this.observers_,n=e.indexOf(t);~n&&e.splice(n,1),!e.length&&this.connected_&&this.disconnect_()},t.prototype.refresh=function(){this.updateObservers_()&&this.refresh()},t.prototype.updateObservers_=function(){var t=this.observers_.filter((function(t){return t.gatherActive(),t.hasActive()}));return t.forEach((function(t){return t.broadcastActive()})),t.length>0},t.prototype.connect_=function(){J&&!this.connected_&&(document.addEventListener("transitionend",this.onTransitionEnd_),window.addEventListener("resize",this.refresh),Z?(this.mutationsObserver_=new MutationObserver(this.refresh),this.mutationsObserver_.observe(document,{attributes:!0,childList:!0,characterData:!0,subtree:!0})):(document.addEventListener("DOMSubtreeModified",this.refresh),this.mutationEventsAdded_=!0),this.connected_=!0)},t.prototype.disconnect_=function(){J&&this.connected_&&(document.removeEventListener("transitionend",this.onTransitionEnd_),window.removeEventListener("resize",this.refresh),this.mutationsObserver_&&this.mutationsObserver_.disconnect(),this.mutationEventsAdded_&&document.removeEventListener("DOMSubtreeModified",this.refresh),this.mutationsObserver_=null,this.mutationEventsAdded_=!1,this.connected_=!1)},t.prototype.onTransitionEnd_=function(t){var e=t.propertyName,n=void 0===e?"":e;Y.some((function(t){return!!~n.indexOf(t)}))&&this.refresh()},t.getInstance=function(){return this.instance_||(this.instance_=new t),this.instance_},t.instance_=null,t}(),et=function(t,e){for(var n=0,o=Object.keys(e);n<o.length;n++){var i=o[n];Object.defineProperty(t,i,{value:e[i],enumerable:!1,writable:!1,configurable:!0})}return t},nt=function(t){return t&&t.ownerDocument&&t.ownerDocument.defaultView||K},ot=lt(0,0,0,0);function it(t){return parseFloat(t)||0}function st(t){for(var e=[],n=1;n<arguments.length;n++)e[n-1]=arguments[n];return e.reduce((function(e,n){return e+it(t["border-"+n+"-width"])}),0)}function rt(t){var e=t.clientWidth,n=t.clientHeight;if(!e&&!n)return ot;var o=nt(t).getComputedStyle(t),i=function(t){for(var e={},n=0,o=["top","right","bottom","left"];n<o.length;n++){var i=o[n],s=t["padding-"+i];e[i]=it(s)}return e}(o),s=i.left+i.right,r=i.top+i.bottom,a=it(o.width),c=it(o.height);if("border-box"===o.boxSizing&&(Math.round(a+s)!==e&&(a-=st(o,"left","right")+s),Math.round(c+r)!==n&&(c-=st(o,"top","bottom")+r)),!function(t){return t===nt(t).document.documentElement}(t)){var l=Math.round(a+s)-e,d=Math.round(c+r)-n;1!==Math.abs(l)&&(a-=l),1!==Math.abs(d)&&(c-=d)}return lt(i.left,i.top,a,c)}var at="undefined"!=typeof SVGGraphicsElement?function(t){return t instanceof nt(t).SVGGraphicsElement}:function(t){return t instanceof nt(t).SVGElement&&"function"==typeof t.getBBox};function ct(t){return J?at(t)?function(t){var e=t.getBBox();return lt(0,0,e.width,e.height)}(t):rt(t):ot}function lt(t,e,n,o){return{x:t,y:e,width:n,height:o}}var dt=function(){function t(t){this.broadcastWidth=0,this.broadcastHeight=0,this.contentRect_=lt(0,0,0,0),this.target=t}return t.prototype.isActive=function(){var t=ct(this.target);return this.contentRect_=t,t.width!==this.broadcastWidth||t.height!==this.broadcastHeight},t.prototype.broadcastRect=function(){var t=this.contentRect_;return this.broadcastWidth=t.width,this.broadcastHeight=t.height,t},t}(),ht=function(t,e){var n,o,i,s,r,a,c,l=(o=(n=e).x,i=n.y,s=n.width,r=n.height,a="undefined"!=typeof DOMRectReadOnly?DOMRectReadOnly:Object,c=Object.create(a.prototype),et(c,{x:o,y:i,width:s,height:r,top:i,right:o+s,bottom:r+i,left:o}),c);et(this,{target:t,contentRect:l})},ut=function(){function t(t,e,n){if(this.activeObservations_=[],this.observations_=new U,"function"!=typeof t)throw new TypeError("The callback provided as parameter 1 is not a function.");this.callback_=t,this.controller_=e,this.callbackCtx_=n}return t.prototype.observe=function(t){if(!arguments.length)throw new TypeError("1 argument required, but only 0 present.");if("undefined"!=typeof Element&&Element instanceof Object){if(!(t instanceof nt(t).Element))throw new TypeError('parameter 1 is not of type "Element".');var e=this.observations_;e.has(t)||(e.set(t,new dt(t)),this.controller_.addObserver(this),this.controller_.refresh())}},t.prototype.unobserve=function(t){if(!arguments.length)throw new TypeError("1 argument required, but only 0 present.");if("undefined"!=typeof Element&&Element instanceof Object){if(!(t instanceof nt(t).Element))throw new TypeError('parameter 1 is not of type "Element".');var e=this.observations_;e.has(t)&&(e.delete(t),e.size||this.controller_.removeObserver(this))}},t.prototype.disconnect=function(){this.clearActive(),this.observations_.clear(),this.controller_.removeObserver(this)},t.prototype.gatherActive=function(){var t=this;this.clearActive(),this.observations_.forEach((function(e){e.isActive()&&t.activeObservations_.push(e)}))},t.prototype.broadcastActive=function(){if(this.hasActive()){var t=this.callbackCtx_,e=this.activeObservations_.map((function(t){return new ht(t.target,t.broadcastRect())}));this.callback_.call(t,e,t),this.clearActive()}},t.prototype.clearActive=function(){this.activeObservations_.splice(0)},t.prototype.hasActive=function(){return this.activeObservations_.length>0},t}(),mt="undefined"!=typeof WeakMap?new WeakMap:new U,ft=function t(e){if(!(this instanceof t))throw new TypeError("Cannot call a class as a function.");if(!arguments.length)throw new TypeError("1 argument required, but only 0 present.");var n=tt.getInstance(),o=new ut(e,n,this);mt.set(this,o)};["observe","unobserve","disconnect"].forEach((function(t){ft.prototype[t]=function(){var e;return(e=mt.get(this))[t].apply(e,arguments)}}));void 0!==K.ResizeObserver&&K.ResizeObserver;const bt=class extends Event{constructor(t,e={}){super(bt.parentWindowEvent),this.eventName=t,this.eventParameters=e}get name(){return this.eventName}get parameters(){return this.eventParameters}};bt.parentWindowEvent="IframeClientEvent";Object.defineProperty,Object.getOwnPropertySymbols,Object.prototype.hasOwnProperty,Object.prototype.propertyIsEnumerable;Object.defineProperty,Object.getOwnPropertySymbols,Object.prototype.hasOwnProperty,Object.prototype.propertyIsEnumerable;
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */const pt=V,{$:gt}=window;class wt{extend(e){e.getContainer().on("click",t.submitAction,(t=>{this.submit(t,e)}))}submit(t,e){const n=gt(t.currentTarget),o=n.data("confirm-message"),i=n.data("confirmTitle");void 0!==o&&o.length>0?void 0!==i?this.showConfirmModal(n,e,o,i):window.confirm(o)&&this.postForm(n,e):this.postForm(n,e)}showConfirmModal(t,e,n,o){const i=t.data("confirmButtonLabel"),r=t.data("closeButtonLabel"),a=t.data("confirmButtonClass");new pt({id:s(e.getId()),confirmTitle:o,confirmMessage:n,confirmButtonLabel:i,closeButtonLabel:r,confirmButtonClass:a},(()=>this.postForm(t,e))).show()}postForm(t,e){const n=gt(b(e.getId()));n.attr("action",t.data("form-url")),n.attr("method",t.data("form-method")),n.submit()}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:vt}=window;class _t{extend(t){t.getContainer().on("click",".js-submit-row-action",(e=>{e.preventDefault();const n=vt(e.currentTarget),o=n.data("confirmMessage"),i=n.data("title"),s=n.data("method");if(i)this.showConfirmModal(n,t,o,i,s);else{if(o.length&&!window.confirm(o))return;this.postForm(n,s)}}))}postForm(t,e){const n=["GET","POST"].includes(e),o=vt("<form>",{action:t.data("url"),method:n?e:"POST"}).appendTo("body");n||o.append(vt("<input>",{type:"_hidden",name:"_method",value:e})),o.submit()}showConfirmModal(t,e,n,o,i){const r=t.data("confirmButtonLabel"),a=t.data("closeButtonLabel"),c=t.data("confirmButtonClass");new V({id:s(e.getId()),confirmTitle:o,confirmMessage:n,confirmButtonLabel:r,closeButtonLabel:a,confirmButtonClass:c},(()=>this.postForm(t,i))).show()}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:yt}=window;class kt{extend(t){this.initRowLinks(t),this.initConfirmableActions(t)}initConfirmableActions(t){t.getContainer().on("click",e.linkRowAction,(t=>{const e=yt(t.currentTarget).data("confirm-message");e.length&&!window.confirm(e)&&t.preventDefault()}))}initRowLinks(t){yt("tr",t.getContainer()).each((function(){const t=yt(this);yt(e.linkRowActionClickableFirst,t).each((function(){const n=yt(this),o=n.closest("td"),i=yt(e.clickableTd,t).not(o);let s=!1;i.addClass("cursor-pointer").mousedown((()=>{yt(window).mousemove((()=>{s=!0,yt(window).unbind("mousemove")}))})),i.mouseup((()=>{const t=s;if(s=!1,yt(window).unbind("mousemove"),!t){const t=n.data("confirm-message");(!t.length||window.confirm(t)&&n.attr("href"))&&(document.location.href=n.attr("href"))}}))}))}))}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */const Ct={selectAll:".js-choice-table-select-all"},{$:Et}=window;class Bt{constructor(){Et(document).on("change",Ct.selectAll,(t=>{this.handleSelectAll(t)}))}handleSelectAll(t){const e=Et(t.target),n=e.is(":checked");e.closest("table").find("tbody input:checkbox").prop("checked",n)}}
  /**
   * Copyright since 2007 PrestaShop SA and Contributors
   * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
   *
   * NOTICE OF LICENSE
   *
   * This source file is subject to the Open Software License (OSL 3.0)
   * that is bundled with this package in the file LICENSE.md.
   * It is also available through the world-wide-web at this URL:
   * https://opensource.org/licenses/OSL-3.0
   * If you did not receive a copy of the license and are unable to
   * obtain it through the world-wide-web, please send an email
   * to license@prestashop.com so we can send you a copy immediately.
   *
   * DISCLAIMER
   *
   * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
   * versions in the future. If you wish to customize PrestaShop for your
   * needs please refer to https://devdocs.prestashop.com/ for more information.
   *
   * @author    PrestaShop SA and Contributors <contact@prestashop.com>
   * @copyright Since 2007 PrestaShop SA and Contributors
   * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
   */
  const{$:Ot}=window;Ot((()=>{const t=new w("webpay_transactions");t.addExtension(new k),t.addExtension(new B),t.addExtension(new x),t.addExtension(new j),t.addExtension(new M),t.addExtension(new wt),t.addExtension(new _t),t.addExtension(new kt),new Bt}))})(),window.webpay_transactions=o})();