<?php


namespace WpLandingKit\Providers;


use WpLandingKit\Ajax\FetchPostsForMapAssignmentAjaxHandler;
use WpLandingKit\Ajax\FetchTermsForMapAssignmentAjaxHandler;
use WpLandingKit\Framework;
use WpLandingKit\Upgrade\Upgrades\Ajax\VersionOneDotOneDataMigration;


class AjaxServiceProvider extends Framework\Ajax\AjaxServiceProvider {


	protected $ajax_handlers = [
		FetchPostsForMapAssignmentAjaxHandler::class,
		FetchTermsForMapAssignmentAjaxHandler::class,

		// Upgrade AJAX handlers.
		// It would be ideal if we could build in a system for registering these on this service provider but from
		// within the UpgradeServiceProvider for better containment.
		VersionOneDotOneDataMigration::class,
	];


}