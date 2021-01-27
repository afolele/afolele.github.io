<?php


namespace WpLandingKit\Upgrade;


use Exception;
use WpLandingKit\Framework\Facades\App;
use WpLandingKit\Upgrade\Upgrades\Ajax\UpgradeAjaxHandlerBase;


abstract class UpgradeBase {


	/** @var null|string The classname of this object's AJAX handler */
	protected $ajax_handler;


	/**
	 * Return the version number of this upgrade. Use the current UNIX timestamp for this. Most readable approach is to
	 * use PHP's `strtotime()` function with a human-readable date. e.g; `strtotime( '1 January 2020' )`.
	 *
	 * @return integer
	 */
	abstract public function version();


	/**
	 * Run the upgrade and report on status.
	 *
	 * @return null|string Null or HTML markup on success.
	 * @throws Exception on failure.
	 */
	abstract public function run();


	/**
	 * The upgrade title.
	 *
	 * @return string
	 */
	abstract public function title();


	/**
	 * Check for any additional conditions that may make this upgrade redundant. You don't need to check for version
	 * numbers here as that is already happening in the \WpLandingKit\Upgrade\Upgrader() but if there are any other
	 * conditions to check before queuing this upgrade up to run, do so here.
	 *
	 * @return bool
	 */
	public function should_run() {
		return true;
	}


	/**
	 * A short description of the upgrade — basically an excerpt.
	 *
	 * @return string Can be HTML but assume this will always end up inside a <p> tag.
	 */
	public function description() {
		return '';
	}


	/**
	 * A detailed explanation of the upgrade.
	 *
	 * @return string HTML markup with the details related to the upgrade. Use <p> and <ul> tags where it makes sense.
	 */
	public function explanation() {
		return '';
	}


	public function is_ajax() {
		return ! empty( $this->ajax_handler );
	}


	/**
	 * @return UpgradeAjaxHandlerBase|null
	 */
	public function ajax_handler() {
		return $this->ajax_handler
			? App::make( $this->ajax_handler )
			: null;
	}


}