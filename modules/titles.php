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
				//return "Missing 'p' (page) argument in query string";
			if (!is_numeric($_REQUEST['p']))
				$_REQUEST['p']=1;
				//return "Page 'p' argument in query string not numeric";
			if ($_REQUEST['p'] <= 0)
				$_REQUEST['p']=1;
				//return "Page 'p' argument invalid value";
			return false;
		}

		function do_work($smarty, $db) {
			global $fullcomments;
			
			if (isset($_GET['id'])){
				$fullcomments=TRUE;
				$books = $db->all_books(true,FALSE, $_GET['id']);
			}else{
				$books = $db->all_books(true);
			}
			$this->do_books($smarty, $db, $books);
			$smarty->assign('up_url', 'index.php');
		}

		function template() {
			return 'titles.tpl';
		}
	}
	
	$mod = new DoTitles();
?>
