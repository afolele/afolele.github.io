const $ = window.jQuery;
const uniqid = require('locutus/php/misc/uniqid');

WplkMappingRow.prototype.make = ($element) => {
    return new WplkMappingRow($element);
};

/**
 * @param data
 * @returns {string} The markup
 */
WplkMappingRow.prototype.tpl = (data) => {
    return $('#wplk-tpl-mapping').html()
        .replace(/{{TPL_PROTOCOL}}/g, data.protocol || '')
        .replace(/{{TPL_DOMAIN}}/g, data.domain || '')
        .replace(/{{TPL_PATH}}/g, data.path || '')
        .replace(/{{TPL_PREVIEW_SUFFIX}}/g, data.preview_suffix || '')
        .replace(/{{TPL_DETAIL_SUFFIX}}/g, data.detail_suffix || '')
        .replace(/{{TPL_MAPPING_ID}}/g, data.mapping_id || uniqid())
        .replace(/{{TPL_REDIRECT_STATUS}}/g, data.redirect_status || '');
};


function WplkMappingRow($element) {

    const $this = $element;
    const $panel = $this.find('.WplkMapping__panel');
    const $toggle = $this.find('.WplkMapping__toggle');
    const $action_field = $this.find('.WplkField--mapping-action');
    const $resource_field = $this.find('.WplkField--mapping-resource');
    const $redirect_field = $this.find('.WplkField--mapping-redirect');
    const $type_field = $this.find('.WplkResourceFields__field--type');
    const $url_path_field = $this.find('.WplkPrefixedTextInput__url-path');
    const $pagination_support = $this.find('.WplkPaginationFields');
    const $action_preview = $this.find('.WplkMapping__type');
    const $url_path_preview = $this.find('.WplkMapping__url-path');
    const $mapping_preview = $this.find('.WplkMapping__mapping');
    const $domain_preview = $this.find('.WplkDomain');
    const mappings = {};

    this.panel_initialised = false;

    this.init = () => {
        // Open/close the panel on click
        $toggle.click((e) => {
            e.preventDefault();
            this.toggle_panel();
        });

        // Configure select field visibility and data resets as mapping fields are given values.
        $this.find('select').on('change', (e, evaluate) => {

            if (evaluate === false) {
                return;
            }

            const select_id = $(e.target).data('select-id') || null;
            if (!select_id) {
                return;
            }
            const select = mappings[select_id] || null;
            if (!select) {
                return;
            }

            const field = select.data('select-id');

            if (field === 'post_type' && this.get_mapping_value('resource_type') === 'single-post') {
                if (select.val()) {
                    this.empty_field_value('p');
                    this.limit_mapping_fields_to(['resource_type', 'post_type', 'p']);
                } else {
                    this.limit_mapping_fields_to(['resource_type', 'post_type']);
                }

            } else if (field === 'resource_type') {
                switch (select.val()) {
                    case 'single-page':
                        this.limit_mapping_fields_to(['resource_type', 'page_id']);
                        $pagination_support.hide();
                        break;
                    case 'single-post':
                        if (this.get_mapping_value('post_type')) {
                            this.limit_mapping_fields_to(['resource_type', 'post_type', 'p']);
                        } else {
                            this.limit_mapping_fields_to(['resource_type', 'post_type']);
                        }
                        $pagination_support.hide();
                        break;
                    case 'post-type-archive':
                        this.limit_mapping_fields_to(['resource_type', 'post_type']);
                        $pagination_support.show();
                        break;
                    case 'taxonomy-term-archive':
                        this.limit_mapping_fields_to(['resource_type', 'taxonomy']);
                        $pagination_support.show();
                        break;
                    default:
                        this.limit_mapping_fields_to(['resource_type']);
                }

            } else if (field === 'taxonomy') {
                if (select.val()) {
                    this.empty_field_value('term_id');
                    this.limit_mapping_fields_to(['resource_type', 'taxonomy', 'term_id']);
                } else {
                    this.limit_mapping_fields_to(['resource_type', 'taxonomy']);
                }
            }
        });

        // Handle row removal
        $this.on('click', '.WplkMapping__remove', (e) => {
            const confirmed = confirm("Are you sure? This can't be undone.");
            if (confirmed !== true) {
                e.preventDefault();
                return;
            }

            this.remove();
        });

        $(window).on('wplk/domain_changed', (e, title) => {
            $domain_preview.text(title);
        });

        this.init_preview();
    };

    this.toggle_panel = () => {
        $this.toggleClass('WplkMapping--open');
        if (!this.panel_initialised) {
            this.panel_initialised = true;
            this.init_panel();
        }
        $panel.slideToggle(160);
    };

    this.init_preview = () => {
        const set_url_path_preview = () => {
            if ($url_path_field.length) {
                $url_path_preview.text($url_path_field.val() || '');
            }
        };
        const set_action_preview = () => {
            let preview_text = {
                map_to_resource: 'maps to',
                redirect: 'redirects to',
            };
            $action_preview.text(preview_text[this.get_action()] || '');
        };
        const set_type_preview = () => {
            const action = this.get_action();
            let text = '';
            if (action === 'map_to_resource') {
                text = $type_field.find('option:selected').data('preview');
            } else if (action === 'redirect') {
                text = $this.find('.WplkRedirectFields__url input').val();
            }

            $mapping_preview.text(text || '');
        };

        set_url_path_preview();
        $url_path_field.on('keyup', () => set_url_path_preview());

        set_action_preview();
        $action_field.on('change', 'input[type="radio"]', () => {
            set_action_preview();
            set_type_preview();
        });

        set_type_preview();
        $type_field.on('change', 'select', () => set_type_preview());
        $this.find('.WplkRedirectFields__url input').on('keyup', () => set_type_preview());
    };

    this.init_panel = () => {
        // Strip placeholder text — this just clutters the Select2 field.
        $this.find('option[disabled][selected]').text('');

        // Initialise each select field as a Select2
        $this.find('select').each((i, element) => {
            const $select = $(element);
            const opts = $select.data('select-opts') || {};
            const select_id = $select.data('select-id') || null;

            let config = {
                placeholder: opts.placeholder || 'Choose',
                minimumResultsForSearch: 10,
                allowClear: false,
                width: opts.width || '100%',
                language: {
                    noResults: function (params) {
                        return $('<span class="wplk-select2-no-results">No results found</span>');
                    }
                }
            };

            if (opts.no_results_text) {
                config.language.noResults = (params) => {
                    return $(`<span class="wplk-select2-no-results">${opts.no_results_text}</span>`);
                };
            }

            if (opts.ajax) {
                config.minimumInputLength = 1;
                config.ajax = {
                    url: opts.ajax.url,
                    dataType: 'json',
                    delay: 250,
                    cache: true,
                    data: (params) => {
                        return {
                            q: params.term,
                            post_type: opts.ajax.post_type || this.get_mapping_value('post_type') || '',
                            taxonomy: opts.ajax.taxonomy || this.get_mapping_value('taxonomy') || '',
                            // domain_id: data.domain_id,
                            // selected_id: data.selected_id || 0,
                            // we already have these in the URL.
                            // action: opts.ajax.vars.action,
                            // _wpnonce: opts.ajax.vars.nonce,
                        };
                    },
                    processResults: (response) => {
                        const options = [];
                        if (response.matches) {
                            $.each(response.matches, (index, post) => {
                                options.push({
                                    id: post.post_id,
                                    text: post.title,
                                    //mapped_post: post.mapped_post,
                                    //disabled: !!post.mapped_post,
                                });
                            });
                        }
                        return {
                            results: options
                        };
                    },
                };
            }

            const select = $select.select2(config);
            if (select_id) {
                mappings[select_id] = select;

                if (select_id !== 'resource_type') {
                    if (!select.val()) {
                        select.wplkSelect2Visibility('hide');
                    }
                }
            }

            // Hide pagination checkbox if a supported resource isn't selected.
            if ($.inArray(this.get_mapping_value('resource_type'), ['post-type-archive', 'taxonomy-term-archive']) < 0) {
                $pagination_support.hide();
            }
        });

        // Initialise top-level field visibility
        this.set_top_field_visibility_for_action(
            $this.find('.WplkField--mapping-action').find('input[type="radio"]:checked').val() || 'map_to_resource'
        );

        // Handle regex checkbox
        $this.find('.WplkRegExCheckbox').on('click', '.WplkRegExCheckbox__checkbox', (e) => {
            // cache the placeholder
            if (!$url_path_field.data('placeholder')) {
                $url_path_field.data('placeholder', $url_path_field.attr('placeholder'));
            }
            if ($(e.target).is(':checked')) {
                $url_path_field.attr('placeholder', $url_path_field.data('regex-placeholder'));
            } else {
                $url_path_field.attr('placeholder', $url_path_field.data('placeholder'));
            }
        });

        $action_field.on('change', 'input[type="radio"]', (e) => {
            this.set_top_field_visibility_for_action($(e.target).val());
        });
    };

    this.set_top_field_visibility_for_action = (action) => {
        switch (action) {
            case 'map_to_resource':
                $redirect_field.hide();
                $resource_field.show();
                break;
            case 'redirect':
                $redirect_field.show();
                $resource_field.hide();
                break;
            default:
                $redirect_field.hide();
                $resource_field.hide();
        }
    };

    this.get_mapping_value = (field_name) => {
        return mappings[field_name].val();
    };

    this.limit_mapping_fields_to = (field_names) => {
        $.each(mappings, (name, select) => {
            if ($.inArray(name, field_names) < 0) {
                //select.val(null).trigger('change', false);
                this.empty_field_value(name);
                select.wplkSelect2Visibility('hide');
            } else {
                select.wplkSelect2Visibility('show');
            }
        });
    };

    this.empty_field_value = (field_name) => {
        mappings[field_name].val(null).trigger('change', false);
    };

    this.get_action = () => {
        return $action_field.find('input[type="radio"]:checked').val() || '';
    };

    this.remove = () => {
        this.destroy();
        $this.slideUp(160, () => $this.remove());
    };

    this.destroy = () => {
        // if we need to de-init anything for this instance, do it here.
    };

}

export default WplkMappingRow;