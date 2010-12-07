<?php
	/*
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'module.php';

	/*
	 * Provide some standard information to smarty. Put values here that are
	 * handler-independent and that you want to use in a template.
	 */

	class DoStandardVars extends Module {

		function do_work($smarty, $db) {
			global $config;

			$smarty->assign('current_date', date('D M j G:i:s T Y'));
			$smarty->assign('current_library', $db->libpath);
			$smarty->assign('page_title', $config['page_title']);
			$smarty->assign('current_version', $config['current_version']);

			if (isset($_SESSION['last_search']))
				$smarty->assign('last_search', $_SESSION['last_search']);
			else
				$smarty->assign('last_search', '');
			if (isset($_SESSION['search_error']))
				$smarty->assign('search_error', $_SESSION['search_error']);
			else
				$smarty->assign('search_error', '');

		}

		function template() {
			return NULL;
		}
	}
	$mod = new DoStandardVars();
?>