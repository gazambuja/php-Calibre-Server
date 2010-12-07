<?php
	/*
		This is a derived work.
		Derived from: 	Calibre, licensed under GPL v3
						http://calibre-ebook.com

		Changes:
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	/*
	 * This function is required by the database. It is probably never called
	 * because it is found in an update trigger.
	 */
	function title_sort($title) {
		$title = trim($title);
		if (preg_match('/^(A|The|An)\s+/i', $title, $match)) {
			$prep = $match[1];
			$title = substr($title, strlen($prep)) . ', ' . $prep;
		}
		return trim($title);
	}

	function case_insensitive_path($prefix, $path) {
		if (!isset($_SESSION['path_map']))
			$_SESSION['path_map'] = array();

		// Have we seen this name before?
		if (array_key_exists($path, $_SESSION['path_map']))
			return $_SESSION['path_map'][$path];

		// base case -- file exists
		$p = $prefix . '/' . $path;
		if (file_exists($p)) {
			$_SESSION['path_map'][$path] = $p;
			return $p;
		}

		// file doesn't exist. Do the case checks
		$segments = array();
		$rest = $path;
		$cc = 0;
		do {
			$ans = pathinfo($rest);
			$segments[] = $ans['basename'];
			$rest = $ans['dirname'];
			if (++$cc > 5) { // something has gone wrong with the loop. Bail out.
				$_SESSION['path_map'][$path] = false;
				return false;
			}
		} while ($rest != '.');

		$p = $prefix;
		for ($i = count($segments)-1; $i >= 0; $i--) {
			$names = scandir($p);
			for ($j = 0; $j < count($names); $j++) {
				if ($j < count($names) - 1 &&
							strcasecmp($names[$j], $names[$j+1]) == 0) {
					dprint("name clash $names[$j]");
					$_SESSION['path_map'][$path] = false;
					return false; // name clash. can't know which to use
				}
				if (strcasecmp($segments[$i], $names[$j]) == 0) {
					$p .= '/' . $names[$j];
					continue 2;
				}
			}
			$_SESSION['path_map'][$path] = false;
			return false;
		}
		$_SESSION['path_map'][$path] = $p;
		return $p;
	}

	/*
	 * map category names to their respective icons.
	 */

	$category_icon_map = array(
					'authors'   => 'user_profile.png',
					'series'    => 'series.png',
					'formats'   => 'book.png',
					'publisher' => 'publisher.png',
					'rating'    => 'rating.png',
					'news'      => 'news.png',
					'tags'      => 'tags.png',
					':custom'   => 'column.png',
					':user'     => 'drawer.png',
					'search'    => 'search.png'
			);

	function get_icon_path($key, $db) {
		global $category_icon_map;

		$prefix = 'images/';
		if ($db->fm->is_custom_field($key))
			return $prefix . $category_icon_map[':custom'];
		if ($db->fm->is_user_category($key))
			return $prefix . $category_icon_map[':user'];
		if (array_key_exists($key, $category_icon_map))
			return $prefix . $category_icon_map[$key];
		return $prefix . 'book.png';
	}

	/*
	 * Format a date using the same format strings that calire's GUI uses.
	 */

	class MyDate {
		function __construct($dt_string) {
			$this->dt = new Datetime($dt_string);
		}

		function format_day ($matches) {
			$l = strlen($matches[0]);
			if ($l == 1) return $this->dt->format('j');
			if ($l == 2) return $this->dt->format('d');
			if ($l == 3) return $this->dt->format('D');
			return $this->dt->format('l');
		}

		function format_month($matches) {
			$l = strlen($matches[0]);
			if ($l == 1) return $this->dt->format('n');
			if ($l == 2) return $this->dt->format('m');
			if ($l == 3) return $this->dt->format('M');
			return $this->dt->format('F');
		}

		 function format_year($matches) {
			$l = strlen($matches[0]);
			if ($l == 2) return $this->dt->format('y');
			return $this->dt->format('Y');
		}
	}

	function format_date($date, $format) {
		if (! $format)
			$format = 'dd MMM yyyy';

		$date = new MyDate($date);
		$format = preg_replace_callback('/d{1,4}/', array($date, 'format_day'), $format);
		$format = preg_replace_callback('/M{1,4}/', array($date, 'format_month'), $format);
		$format = preg_replace_callback('/yyyy|yy/', array($date, 'format_year'), $format);
		return $format;
	}

	/*
	 * Determine the restriction string to use, if any
	 */
	function restriction_to_apply() {
		global $config;

		if (isset($_SERVER['REMOTE_USER']))
			$user = $_SERVER['REMOTE_USER'];
		else
			$user = '*';

		$r = $config['restrict_display_to'];
		if ($r) {
			if (is_array($r)) {
				if (array_key_exists($user, $r))
					return $r[$user];
				if (array_key_exists('*', $r))
					return $r['*'];
				return '';
			}
		}
		return $r;
	}

	/*
	 * A debug print function that tried to put the output where it can later
	 * be found.
	 */
	function dprint($msg) {
		if (PHP_OS == 'Linux')
			error_log($msg . "\n");
		else
			error_log($msg . "\n", 3, 'c:/php_errors.txt');
	}
?>