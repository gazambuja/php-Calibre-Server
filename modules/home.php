<?php
	/*
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'module.php';

	/*
	 * The handler for the index (front) page. Gets the categories. Fixed
	 * 'categories', such as titles, are handled in the template.
	 */
	class DoHome extends Module {

		function do_work($smarty, $db) {
			$cats = $db->get_categories();
			$items = array();
			foreach ($db->get_categories() as $k => $v) {
				$m = $db->fm->metadata_for($k);
				$items[] = array(	'icon' => get_icon_path($k, $db),
									'name' => $m['name'],
									'href' => "index.php?m=category&cat=" . urlencode($k),
									'count'=> count($v)
								);
			}
			$smarty->assign('categories', $items);
			$smarty->assign('title_count', $db->all_books_count());
		}

		function template() {
			return 'index.tpl';
		}
	}
	$mod = new DoHome();
?>