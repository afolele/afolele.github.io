<?php


namespace WpLandingKit\View;


use WpLandingKit\Framework\View\StaticViewAccess;


/**
 * Class View
 * @package WpLandingKit\View
 */
class View {

	/*
	 * Using this trait provides us with a static interface for our view handling classes. This
	 * allows us to have multiple view classes that don't interfere with each other.
	 */
	use StaticViewAccess;

}
