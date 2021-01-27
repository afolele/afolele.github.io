const $ = window.jQuery;

function WplkProgress($element) {
    this.$el = $element;
    this.$percent_indicator = $element.find('.WplkProgress__percentage');
}

WplkProgress.prototype.start = function () {
    this.set_status('loading');
};

WplkProgress.prototype.complete = function () {
    this.set_progress(100);
    this.set_status('complete');
};

WplkProgress.prototype.error = function () {
    this.set_status('error');
};

WplkProgress.prototype.set_status = function (status) {
    this.$el.attr('data-status', status);
};

WplkProgress.prototype.set_progress = function (progress) {
    let value = Math.abs(parseInt(progress));

    if (value > 100) {
        value = 100;
    }

    if (value < 0) {
        value = 0;
    }
    this.$el.attr('data-progress', value);
    this.$percent_indicator.text(value + '%');
};

export default WplkProgress;