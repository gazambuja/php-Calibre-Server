<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'config.php';
	$config['current_version'] = '0.2.1';

	require_once 'db.php';
	require_once 'utilities.php';
	require_once 'smarty.php';

	/*
	 * This is the entry point for all processing.
	 *
	 * It is imperative that this code do *no* output, leaving that to the
	 * handlers (see below). The reason is that the handlers need the freedom
	 * to output special headers.
	 */

	session_start();

	$db = new CalDatabase($config['library_dir']);

	/*
	 * Handle searches immediately. Compute the result set, then redo the
	 * last page request.
	 */
	if (isset($_REQUEST['m']) && $_REQUEST['m'] == 'search') {
		$db->search($_REQUEST['query']);
		$_SESSION['last_search'] = $_REQUEST['query'];
		$_REQUEST = $_SESSION['last_request'];
	}
	if ($config['restrict_display_to'] && !isset($_SESSION['book_filter'])) {
		$db->search('');
	}

	/*
	 * The system is built around requests being 'dispatched' to an appropriate
	 * handler, which is named by the 'm' argument. Be sure that we have one,
	 * and be sure that it is one we know about.
	 */
	if (isset($_REQUEST['m']))
		$submod = $_REQUEST['m'];
	else
		$submod = 'home';

	/*
	 * The handlers we know about. Use this method so we parse only the
	 * handler we are interested in.
	 */
	if (!in_array($submod, array('home', 'category', 'catval', 'cover',
								'titles', 'book_format', 'rating', 'random'))) {
		$smarty->assign('message', "Unknown module $submod in query string");
		$smarty->display('error.tpl');
		exit(0);
	}

	/*
	 * The 'standard vars' handler generates smarty values for system-wide
	 * things, such as timestamps and the current library.
	 */
	require_once('modules/standard_vars.php');
	$mod->do_work($smarty, $db);

	/*
	 * Load the handler.
	 */
	require_once("modules/${submod}.php");
	/*
	 * Ask the handler to verify its arguments.
	 */
	if ($err = $mod->check_arguments($db)) {
		dprint ("check args failed: $err");
		$smarty->assign('message', $err);
		$smarty->display('error.tpl');
		exit(0);
	}

	/*
	 * Invoke the handler. It does whatever it does.
	 */
	$mod->do_work($smarty, $db);
	/*
	 * If the handler is paired with a template (most are), then give the
	 * template to smarty.
	 */
	$template = $mod->template();

	/*
	 * If we had a template, we had a response page. Remember the arguments
	 * so we can redo it if a search intervenes.
	 */
	if (isset($template)) {
		$_SESSION['last_request'] = $_REQUEST;
		$smarty->display($mod->template());
	}
?>
