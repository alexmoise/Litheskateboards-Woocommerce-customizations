/**
 * script.js
 *
 * @author wisslogic
 * @package WiLo WooCommerce Variation Price Hints
 * @version 1.0.0
 */

if (typeof(wm_pvar) === 'undefined') {
    var wm_pvar = {
        products_by_attribute_ids: [],
        products_prices: [],
        products_attributes: [],
        products_attributes_values: [],
        additional_cost_indicator: '+',
        hide_price_when_zero: "true",
        format_string: "{1} ({3}{0} {2})",
        format_string_from: "{1} (ab {0} {2})",
        lowest_price: 0,
        display_style: '0',
        show_from_price: "false",
        product_id: 0
    };
}


jQuery(document).ready(function ($) {
    "use strict";

    var wm_pvar_initial_load = true;

    /**
     * format
     * Replaces placeholder in strings with parameters
     * @type {String.f}
     */
    String.prototype.format = String.prototype.f = function () {
        var s = this,
            i = arguments.length;

        while (i--) {
            s = s.replace(new RegExp('\\{' + i + '\\}', 'gm'), arguments[i]);
        }
        return s;
    };

    /**
     * Number.prototype.format(n, x, s, c)
     *
     * @param integer n: length of decimal
     * @param integer x: length of whole part
     * @param mixed   s: sections delimiter
     * @param mixed   c: decimal delimiter
     */
    Number.prototype.format = function (n, x, s, c) {
        var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\D' : '$') + ')',
            num = this.toFixed(Math.max(0, ~~n));

        return (c ? num.replace('.', c) : num).replace(new RegExp(re, 'g'), '$&' + (s || ''));
    };

    /**
     * jsBaseSelection
     * @returns {boolean}
     */
    function jsBaseSelection() {
        if (wm_pvar_initial_load) {
            wm_pvar_initial_load = false;
            var $selects = $(".variations").find(".select");
            var hasChanged = false;

            $selects.each(function () {
                var $el = $(this).find('.option:eq(0)');
                if (typeof($el) === 'undefined' || $el.val() === '') {
                    $el = $(this).find('.option:eq(1)');
                }
                if (typeof($el) !== 'undefined') {
                    if ($el.attr('selected') !== 'selected') {
                        $el.attr('selected', 'selected');
                        hasChanged = true;
                    }
                }
            });

            // Inform quick view and other plugins
            if (hasChanged)
                $selects.first().trigger('change');
            return true;
        }
        return false;
    }

    /**
     * setFormHandled
     */
    function setFormHandled() {
        $('.variations_form').attr('wl_initialized', '1');
    }

    /**
     * getFormHandled
     * @returns {boolean}
     */
    function getFormHandled() {
        return $('.variations_form').attr('wl_initialized') === '1';
    }

    /**
     * initSelectBoxes
     * Initially save alle select-box texts in attributes
     */
    function initSelectBoxes() {
        // save options text in data attribute
        $(".variations .option").each(function () {
            var t = $(this).attr('data-text-b');
            if (typeof(t) === 'undefined' || t.length <= 0 || t === '') {
                $(this).attr('data-text-b', $(this).text());
            }
        });

        setFormHandled();
    }

    // Helper
    function strFromSelection(selection) {
        return selection.join(',', selection);
    }

    /**
     * moneyString
     * Formats variation dropdown-texts according to admin-settings
     * @param moneyFloat
     * @param currPriceFloat
     * @param text
     * @param isFromPrice
     * @param notFilled
     * @returns {*}
     */
    function moneyString(moneyFloat, currPriceFloat, text, isFromPrice, notFilled) {
        isFromPrice = isFromPrice || false;
        var price = parseFloat((wm_pvar.display_style === 0) ? moneyFloat - currPriceFloat : moneyFloat || 0);
        if (
            (wm_pvar.hide_price_when_zero === true && (moneyFloat - currPriceFloat === 0)) ||
            (wm_pvar.display_style === 1 && price === 0.0) ||
            (isFromPrice && wm_pvar.show_from_price === false) ||
            (!isFromPrice && wm_pvar.show_from_price === false && currPriceFloat === 0)
        ) {
            return text;
        }
        var additional_cost_indicator = (price > 0 && wm_pvar.display_style === 0 && !isFromPrice && currPriceFloat > 0 ? wm_pvar.additional_cost_indicator : '');
        var seperator = wm_pvar.thousands_sep || '';
        var priceStr = price.format(2, 3, seperator, wm_pvar.decimal_sep);
        if (isFromPrice) {
            return wm_pvar.format_string_from.format(priceStr, text, wm_pvar.currency);
        }

        return wm_pvar.format_string.format(priceStr, text, wm_pvar.currency, additional_cost_indicator);
    }

    /**
     * getProductIdFromAttributeSelection
     * Get variation id from currently selected attributes
     * @param $attributes
     * @returns {*}
     */
    function getProductIdFromAttributeSelection($attributes) {
        var strAttributes = strFromSelection($attributes);
        for (var key in wm_pvar.products_by_attribute_ids) {
            if (key === strAttributes)
                return wm_pvar.products_by_attribute_ids[key];
        }
    }


    /**
     * getProductIdsFromAttributeSelection
     * @param $attributes
     * @returns {Array}
     */
    function getProductIdsFromAttributeSelection($attributes) {
        var resArray = [];
        for (var key in wm_pvar.products_by_attribute_ids) {
            var tKey = key.split(',');
            var denied = false;
            for (var i = 0; i < $attributes.length; i++) {
                if ($attributes[i] === '*' || tKey[i] === $attributes[i] || tKey[i] == "-1") {
                } else
                    denied = true;
            }
            if (!denied)
                resArray.push(wm_pvar.products_by_attribute_ids[key]);

        }
        return resArray;
    }

    function getPriceByProductId($productId) {
        for (var key in wm_pvar.products_prices) {
            var intKey = parseInt(key || 0);
            if (intKey === $productId) {
                return parseFloat(wm_pvar.products_prices[key]);

            }
        }
    }

    /**
     * resetDropDowns
     * Restore initial text in dropdowns
     */
    function resetDropDowns() {
        $(document).find(".variations .option").each(function () {
            var nText = $(this).attr('data-text-b') || '';
            if (nText === '') return false;
            $(this).text(nText);
        });
    }

    /**
     * YITH Quickview / general quickview support
     */
    $(document).on('qv_is_closed', function () {
        wm_pvar_initial_load = true;
    });

    function getCurrentState() {
        var localVal = '', matchFound = false,
            currState = {
                notFilled: 0,
                selection: [],
                hasStars: false,
                price: 0,
                product_id: 0
            };

        $(".variations .select .option[selected='selected']").each(function () {
            localVal = $(this).val();
            matchFound = false;
            if (localVal === '') {
                currState.notFilled++;
            } else {
                for (var key in wm_pvar.products_attributes_values) {
                    if (wm_pvar.products_attributes_values[key] === localVal) {
                        currState.selection.push(key);
                        matchFound = true;
                        break;
                    }
                }
            }
            if (!matchFound) {
                currState.selection.push('*');
                currState.hasStars = true;
            }
        });
        currState.product_id = getProductIdFromAttributeSelection(currState.selection) || 0;
        currState.price = getPriceByProductId(currState.product_id) || 0;
        return currState;
    }


    /**
     * refreshDropDowns
     * Prints Price information in dropdowns.
     * Fails if one of the dropdowns has no selection.
     * @returns {boolean}
     */
    function refreshDropDowns() {
        // TODO: Check if this has a detectable impact on performance
        // Reinit select boxes (solves user error)
        initSelectBoxes();
        var currState = getCurrentState();

        // fail silently if not all selection boxes are filled and !show_from_price
        if (currState.notFilled > 0 && wm_pvar.show_from_price === false) {
            // jsBaseSelection();
            resetDropDowns();
            return false;
        }

        // loop all variations and calculate prices
        $(document).find(".variations .select").each(function (i1) {
            var na = currState.selection.slice();
            // Loop all elements of current select box
            $(this).find(".option").each(function () {
                if ($(this).val() !== '') {

                    na[i1] = $(this).val();
                    var tmpVal = $(this).val();
                    var tmpFound = false;
                    for (var tmpKey in wm_pvar.products_attributes_values) {
                        if (wm_pvar.products_attributes_values[tmpKey] === tmpVal) {
                            na[i1] = tmpKey;
                            tmpFound = true;
                            break;
                        }
                    }
                    if (tmpFound) {
                        var idA = getProductIdsFromAttributeSelection(na);
                        var tmpMin = 0;
                        for (var i = 0; i < idA.length; i++) {

                            var tmpMinPrice = parseFloat(getPriceByProductId(idA[i]));
                            if (tmpMinPrice < tmpMin || tmpMin === 0) {
                                tmpMin = tmpMinPrice;
                            }
                        }

                        var text = moneyString(parseFloat(tmpMin || 0), currState.price, $(this).attr('data-text-b') || '', idA.length > 1, currState.notFilled);
                        $(this).text(text);
                        var $form = $('.variations_form');
                        $form.trigger('woocommerce_update_variation_values');
                    }
                }
            });
        });
        // selection boxes have been updated successfully
        return true;
    }

    $(this).on('reset_data', function (event, variation) {
        resetDropDowns();
    });


    function generateArrayStrings(products_by_attribute_ids) {
        var strArray = [];
        for (var key in products_by_attribute_ids) {
            strArray.push(strFromSelection(key));
        }
        return strArray;
    }

    function ajaxRefreshPrices() {
        var initialized = getFormHandled();
        if (!initialized)
            initSelectBoxes();
        var $cart = $("form.variations_form");
        if (typeof $cart === 'undefined' || $cart.length <= 0) return;
        var product_id = $cart.attr('data-product_id');

        if (!initialized) {
            // jsBaseSelection();
            wm_pvar_initial_load = false;
            var data = {
                'action': 'wmp_variation_price_array',
                'product_id': product_id
            };
            $.ajax({
                url: wm_pvar.ajax_url,
                type: "POST",
                data: data})
                .always(function (data) {
                    data = data || [];
                    wm_pvar.lowest_price = data['lowest_price'] || wm_pvar.lowest_price;
                    wm_pvar.products_attributes = data['products_attributes'] || wm_pvar.products_attributes;
                    wm_pvar.products_attributes_values = data['products_attributes_values'] || wm_pvar.products_attributes_values;
                    wm_pvar.products_by_attribute_ids = data['products_by_attribute_ids'] || wm_pvar.products_by_attribute_ids;
                    wm_pvar.products_prices = data['products_prices'] || wm_pvar.products_prices;
                    wm_pvar.product_id = $("form.variations_form").attr('data-product_id');
                    initVariables();
                    refreshDropDowns();
            });
        } else {
            refreshDropDowns();
        }
    }

    /**
     * Recalculate prices on every change in variation configuration
     */
    $(document).on('found_variation', function (event, variation) {
        refreshDropDowns(true);
    });
    $(document).on('update_variation_values', function (event, variation) {
        refreshDropDowns();
    });

    $(document).on('check_variations', function (event, variation) {
        ajaxRefreshPrices();
    });

    function initVariables() {
        wm_pvar.hide_price_when_zero = (wm_pvar_settings.hide_price_when_zero === true || wm_pvar_settings.hide_price_when_zero === "true");
        wm_pvar.show_from_price = (wm_pvar_settings.show_from_price === true || wm_pvar_settings.show_from_price === "true");
        wm_pvar.display_style = parseInt(wm_pvar_settings.display_style || 0);
        wm_pvar.lowest_price = parseFloat(wm_pvar_settings.lowest_price || 0);
        wm_pvar.product_id = parseInt(wm_pvar_settings.product_id || 0);
        wm_pvar.num_decimals = parseInt(wm_pvar_settings.num_decimals || 2);
        wm_pvar.decimal_sep = wm_pvar_settings.decimal_sep || ",";
        wm_pvar.thousands_sep = wm_pvar_settings.thousands_sep || "";

        wm_pvar.format_string = wm_pvar_settings.format_string || "{1} ({3}{0} {2})";
        wm_pvar.format_string_from = wm_pvar_settings.format_string_from || "{1} (ab {0} {2})";
        wm_pvar.currency = wm_pvar_settings.currency || "$";
        wm_pvar.additional_cost_indicator = wm_pvar_settings.additional_cost_indicator || '+';
        wm_pvar.ajax_url = wm_pvar_settings.ajax_url || '/wp-admin/admin-ajax.php';
    }

    initVariables();
    ajaxRefreshPrices();

});
