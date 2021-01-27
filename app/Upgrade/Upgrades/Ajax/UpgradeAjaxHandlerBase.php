<?php


namespace WpLandingKit\Upgrade\Upgrades\Ajax;


use WpLandingKit\Framework\Ajax\AjaxHandlerBase;


class UpgradeAjaxHandlerBase extends AjaxHandlerBase {


	protected $print_inline_script = false;


	protected function get_custom_script_vars() {
		return [
			'stage' => 'initial'
		];
	}


	// open visibility for template use
	public function get_script_id() {
		return parent::get_script_id();
	}


	public function do_inline_script() {
		echo $this->get_inline_script();
		?>
        <script>
            window.wp_landing_kit = window.wp_landing_kit || {};
            window.wp_landing_kit.upgrade_ids = window.wp_landing_kit.upgrade_ids || [];
            window.wp_landing_kit.upgrade_ids.push('<?php echo $this->get_script_id() ?>');
        </script>
		<?php
	}


}