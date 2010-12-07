<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'book_base.php';
	
	/*
	 * Handler for lists of books matching a particular category value
	 */

	class DoCatval extends BookBase {

		function check_arguments($db) {
			if (!isset($_REQUEST['id']))
				return "Missing 'id' argument in query string";
			if (!isset($_REQUEST['cat']))
				return "Missing 'cat' argument in query string";
			if (!isset($_REQUEST['v']))
				return "Missing 'v' argument in query string";
			if (!isset($_REQUEST['p']))
				return "Missing 'p' (page) argument in query string";
			if (!is_numeric($_REQUEST['p']))
				return "Page 'p' argument in query string not numeric";
			if ($_REQUEST['p'] <= 0)
				return "Page 'p' argument invalid value";
			return false;
		}

		function do_work($smarty, $db) {
			$cat = $_REQUEST['cat'];
			$id = $_REQUEST['id'];
			$books = $db->books_in_category($cat, $id);
			$this->do_books($smarty, $db, $books);
			$smarty->assign('category', $_REQUEST['v']);
			$smarty->assign('up_url', "index.php?m=category&id=$id&cat=".urlencode($cat));
		}

		function template() {
			return 'books.tpl';
		}

	}
	$mod = new DoCatval();
?>