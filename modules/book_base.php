<?php
	/*
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/
	require_once 'module.php';

	/*
	 * This handler should never be used directly. It is instead the base class
	 * for handlers that need to produce lists of books.
	 */

	class BookBase extends Module {

		/*
		 * Produce the appropriate values for the smarty templates that renders
		 * a list of books. Mainly concerns paging
		 */
		function do_books($smarty, $db, $books) {
			global $config;

			$page = $_REQUEST['p'];
			$start = ($page-1) * $config['books_page_count'];
			$end = $start + $config['books_page_count'];
			$res = array();
			// Get the books for page N
			for ($i = min($start, count($books)); $i < min($end, count($books)); $i++) {
				$res[] = $books[$i];
				$res[count($res)-1]['cover'] = 'index.php?m=cover&id=' . $books[$i]['id'];
			}
			$smarty->assign('books', $res);
			if ($page > 1)
				$smarty->assign('page_back', 'index.php?m=titles&p=' . ($_REQUEST['p']-1));
			if ($end < count($books))
				$smarty->assign('page_forw', 'index.php?m=titles&p=' . ($_REQUEST['p']+1));
			$smarty->assign('page', $page);
			$smarty->assign('maxpage', (int)((count($books)-1)/$config['books_page_count'])+1);
		}
	}
?>