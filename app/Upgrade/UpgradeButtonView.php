<?php


namespace WpLandingKit\Upgrade;


use WpLandingKit\View\AdminView;


/**
 * Class UpgradeButtonView
 * @package WpLandingKit\Upgrade
 *
 * Render the upgrade button where needed along with the inline confirmation script. The inline script will only render
 * once when loaded via this view helper.
 */
class UpgradeButtonView {


	private $script_rendered = false;


	public function button( $url ) {
		return AdminView::prepare( 'upgrade-button', [ 'url' => $url ] );
	}


	public function script() {
		if ( $this->script_rendered ) {
			return '';
		}

		$this->script_rendered = true;

		return AdminView::prepare( 'upgrade-button-confirm-script' );
	}


}