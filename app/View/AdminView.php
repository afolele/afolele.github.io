<?php


namespace WpLandingKit\View;


use WpLandingKit\Framework\View\StaticViewAccess;


/**
 * Class AdminView
 * @package WpLandingKit\View
 */
class AdminView {

	/*
	 * Using this trait provides us with a static interface for our view handling classes. This
	 * allows us to have multiple view classes that don't interfere with each other.
	 */
	use StaticViewAccess;

}