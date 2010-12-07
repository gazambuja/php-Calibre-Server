<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
		
	*/

	require_once 'module.php';
	
	/*
	 * Handle category pages.
	 */

	class DoCategory extends Module {

		function check_arguments($db) {
			if (!isset($_REQUEST['cat']))
				return "Missing 'cat' argument in query string";
			$cat = $_REQUEST['cat'];
			$this->cats = $db->get_categories();
			if (!array_key_exists($cat, $this->cats))
				return "Unknown category $cat in query string";
			return false;
		}

		function do_work($smarty, $db) {
			$cat = $_REQUEST['cat'];
			$cats = $this->cats;
			$items = array();
			foreach ($cats[$cat] as $k => $v) {
				if ($cat != 'rating' && $v->avg_rating > 0) {
					// item has a rating. Include the URL to display it.
					$items[] = array('icon' => get_icon_path($v->category, $db),
							'name' 		=> $v->name,
							'href' 		=> "index.php?m=catval&id=$v->id&p=1&&cat=" .
											urlencode($v->category) . '&v=' . urlencode($v->name),
							'count'		=> $v->count,
							'rating'	=> "index.php?m=rating&r=" . round($v->avg_rating, 2)
							);
				} else {
					$items[] = array('icon' => get_icon_path($v->category, $db),
							'name' 		=> $v->name,
							'href' 		=> "index.php?m=catval&id=$v->id&p=1&cat=" .
											urlencode($v->category) . '&v=' . urlencode($v->name),
							'count'		=> $v->count
							);
				}
			}
			$smarty->assign('categories', $items);
			$smarty->assign('up_url', 'index.php');
		}

		function template() {
			return 'category.tpl';
		}
	}

	$mod = new DoCategory();
?>