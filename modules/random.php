<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/
	require_once 'config.php';
	
	$config['books_page_count'] = 100;
	$config['cover_max_width'] = 100;
	$config['cover_max_height'] = 100;
	
	require_once 'book_base.php';
	
	/*
	 * Handler for the all-titles page.
	 */

	class DoTitles extends BookBase {

		function check_arguments($db) {
			$_REQUEST['p']=1;
			return false;
		}

		function do_work($smarty, $db) {
			$books = $db->all_books(true, true);
			$this->do_books($smarty, $db, $books);
			$smarty->assign('up_url', 'index.php');
		}

		function template() {
			return 'random.tpl';
		}
	}
	
	$page=FALSE;
	$mod = new DoTitles();
?>
