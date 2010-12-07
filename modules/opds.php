<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'book_base.php';
	require_once 'config.php';
	
	/*
	 * Handler for the all-titles page.
	 */

	class DoTitles extends BookBase {

		function check_arguments($db) {
			if (!isset($_REQUEST['p']))
				$_REQUEST['p']=1;
			if (!is_numeric($_REQUEST['p']))
				$_REQUEST['p']=1;
			if ($_REQUEST['p'] <= 0)
				$_REQUEST['p']=1;
			return false;
		}

		function do_work($smarty, $db) {
			$books = $db->all_books(true);
			$this->do_books($smarty, $db, $books);
			$smarty->assign('up_url', 'index.php');
		}

		function template() {
			return 'opds.tpl';
		}
	}
	
	$mod = new DoTitles();
?>
