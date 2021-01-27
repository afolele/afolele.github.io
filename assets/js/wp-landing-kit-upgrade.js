import WplkUpgrade from "./objects/WplkUpgrade";

(function ($, window, document, undefined) {

    $(function () {

        window.wp_landing_kit = window.wp_landing_kit || {};
        window.wp_landing_kit.upgrade_ids = window.wp_landing_kit.upgrade_ids || [];

        if (!window.wp_landing_kit.upgrade_ids.length) {
            return;
        }

        $.each(window.wp_landing_kit.upgrade_ids, (index, id) => {

            // todo (later) implement a promise to support multiple, sequential AJAX upgrades

            const upgrade = new WplkUpgrade(id);

            if (upgrade.can_run()) {
                upgrade.run();
            }

        });

    });

})(jQuery, window, document);