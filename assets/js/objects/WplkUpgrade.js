import WplkProgress from "./WplkProgress";
import ExtractAjaxVars from "../utils/ExtractAjaxVars";

const $ = window.jQuery;

function WplkUpgrade(id) {
    this.$el = $('#WplkAjaxUpgrade--' + id);
    this.$info = this.$el.find('.WplkAjaxUpgrade__info');
    this.progress_meter = new WplkProgress(this.$el.find('.WplkProgress'));
    this.ajax_vars = ExtractAjaxVars($('#' + id));
}

WplkUpgrade.prototype.can_run = function () {
    return !!this.ajax_vars;
};

WplkUpgrade.prototype.set_info = function (info) {
    this.$info.html(info);
};

WplkUpgrade.prototype.run = function () {
    this.progress_meter.start();
    this.set_info('Running…');
    this.do_ajax(this.ajax_vars);
};

WplkUpgrade.prototype.do_ajax = function (vars) {
    $.ajax({
        url: vars.ajax_url,
        type: 'POST',
        data: {
            action: vars.action,
            _wpnonce: vars.nonce || '',
            stage: vars.stage || '',
            additional: vars.additional || {}
        },
        dataType: 'json'

    }).then((payload, textStatus, jqXhr) => {
        // Ensure the basic structure is in place for error handling where an
        // AJAX request failed to return a payload.
        if (!payload) {
            payload = {
                success: false,
                data: {
                    response: {
                        info: 'For some unknown reason, the AJAX request failed to return any information. Check your error logs and reach out to our support for further assistance.'
                    }
                }
            };
        }
        return payload;

    }).done((payload, textStatus, jqXhr) => {

        if (payload.success) {
            this.set_info(payload.data.response.info);
            this.progress_meter.set_progress(payload.data.response.progress);

            if (payload.data.status === 'done') {
                this.progress_meter.complete();

            } else if (payload.data.status === 'in_progress') {
                this.do_ajax(payload.data.next);
            }

        } else {
            this.set_info(`<div class="WplkCard__error">${payload.data.response.info}</div>`);
            this.progress_meter.error();
        }

    }).fail((jqXhr, status, thrown) => {
        this.set_info('<p>There was an error with the request. The error message reads:</p>' +
            `<p><code>${thrown}</code></p>` +
            "<p>If this isn't a problem that can be resolved locally, please reach out to our support so we can help.</p>");

        this.progress_meter.error();

    });
};

export default WplkUpgrade;