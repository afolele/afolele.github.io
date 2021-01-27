import select2 from 'select2'
import WplkMappingRow from "./objects/WplkMappingRow";


(function ($, window, document, undefined) {

    $(function () {

        const $ui = $('.WplkMappings');
        if (!$ui.length) {
            return;
        }

        const $title = $('#title');
        $title.on('keyup', () => $(window).trigger('wplk/domain_changed', $title.val()));

        const $sortables_container = $ui.find('.WplkMappings__sortable');

        /**
         * Toggle the visibility — using jQuery's `hide` and `show` methods — of the select field's parent. If you use
         * this on anything other than a select2 field wrapped in a containing element, you might get upset. It's ok to
         * shed a tear over code now and then. Just be sure to pick yourself back up and
         *
         * @param state 'hide' || 'show'
         * @returns {*}
         */
        $.fn.wplkSelect2Visibility = function (state) {
            if (state !== 'hide' && state !== 'show') {
                return this;
            }
            return this.each(function () {
                //$.fn[state].call($(this).next('.select2-container'));
                $.fn[state].call($(this).parent());
            });
        };

        // Instantiate any mapping rows in the DOM on page load.
        $ui.find('.WplkMapping').each((i, element) => WplkMappingRow.prototype.make($(element)).init());

        // Enable drag n drop sorting of mapping rows.
        $sortables_container.sortable({
            items: '.WplkMapping',
            handle: '.WplkMapping__icon',
            placeholder: 'WplkMapping--placeholder',
            opacity: 0.8,
            forcePlaceholderSize: true,
        });

        // Handle new mapping generation.
        $ui.find('.WplkMappings__action-add-mapping').on('click', () => {
            const $row = $(WplkMappingRow.prototype.tpl({
                domain: $title.val(),
            }));
            const instance = WplkMappingRow.prototype.make($row);
            instance.init();
            $sortables_container.append($row);
            instance.toggle_panel();
        });
    });


    // Legacy JS — remove asap
    $(function () {

        // if our data block is not available, don't do anything
        var $myData = $('#wp_landing_kit_fetch_mappable_domains');
        if (!$myData.length)
            return;

        let data = {};

        // attempt to parse the content of our data block
        try {
            data = JSON.parse($myData.text());
        } catch (err) { // invalid json
            return;
        }

        let $mapped_page_select = $('#mapped-domain-id');

        if ($mapped_page_select.length) {
            init_select2_page_select();
        }

        /**
         * Initialise the select2 library AJAX search for the mapped domain post select field.
         */
        function init_select2_page_select() {
            $mapped_page_select.select2({
                minimumInputLength: 3,
                allowClear: true,
                placeholder: 'Choose a domain for this post',
                ajax: {
                    url: data.ajax_url,
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: (params) => {
                        return {
                            q: params.term,
                            action: data.action,
                            _wpnonce: data.nonce,
                            domain_id: data.domain_id,
                            selected_id: data.selected_id || 0,
                        };
                    },
                    processResults: (response) => {
                        const options = [];

                        if (response.matches) {
                            $.each(response.matches, (index, post) => {
                                options.push({
                                    id: post.post_id,
                                    text: post.title,
                                    mapped_post: post.mapped_post,
                                    disabled: !!post.mapped_post,
                                });
                            });

                        }
                        return {
                            results: options
                        };
                    },
                },
                templateResult: (state) => {
                    let $opt = $(`<div class="WplkOption"><span class="WplkOption__title">${state.text}</span></div>`);

                    if (state.mapped_post) {
                        $opt.append($(`<span class="WplkOption__mapped-domain-note">&nbsp;&nbsp;(mapped to ${state.mapped_post})</span>`))
                    }
                    return $opt;
                }
            });
        }

    });
    // eof Legacy JS

})(jQuery, window, document);