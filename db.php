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

	require_once "utilities.php";
	require_once "query.php";

	/*
	 * Category lists contain instances of this class
	 */
	class Tag {
		function __construct($name, $id, $count, $avg, $sort, $category) {
			$this->name = $name;
			$this->id = $id;
			$this->count = $count;
			$this->avg_rating = isset($avg) ? $avg/2.0 : 0;
			$this->sort = $sort;
			$this->category = $category;
		}
	}

	/*
	 * A partial implementation of calibre's field_metadata class. The metadata
	 * itself is saved in the preferences table by calibre.
	 */
	class FieldMetadata {
		function __construct($fm_string) {
			$this->fm = json_decode($fm_string, true);
			$this->_search_term_map = array();
			foreach ($this->fm as $k => $v) {
				foreach($v['search_terms'] as $t)
					$this->_search_term_map[$t] = $k;
			}
		}

		function search_term_to_field_key($term) {
			if (array_key_exists($term, $this->_search_term_map))
				return $this->_search_term_map[$term];
			return $term;
		}

		function key_exists($key) {
			return array_key_exists($key, $this->fm);
		}

		function key_to_label($key) {
			return $this->fm[$key]['label'];
		}

		function fm_map() {
			return $this->fm;
		}

		function is_custom_field($key) {
			if (array_key_exists($key, $this->fm))
				return $this->fm[$key]['is_custom'];
			return false;
		}

		function is_user_category($key) {
			if (array_key_exists($key, $this->fm))
				return $this->fm[$key]['kind'] == 'user';
			return false;
		}

		function metadata_for($key) {
			if (array_key_exists($key, $this->fm))
				return $this->fm[$key];
			return NULL;
		}

		function sorted_keys() {
			$keys = array();
			foreach ($this->fm as $k => $m)
				if (!$m['is_custom'] && $m['kind'] == 'field')
					$keys[] = $k;
			natcasesort($keys);
			$tkeys = array();
			foreach ($this->fm as $k => $m)
				if ($m['is_custom'])
					$tkeys[] = $k;
			natcasesort($tkeys);
			$keys = array_merge($keys, $tkeys);
			return $keys;
		}

	}

	/*
	 * This function is referenced in queries to filter the results by books
	 * found in a sort.
	 */
	function books_list_filter($id) {
		if (!isset($_SESSION['book_filter']))
			return True;
		return isset($_SESSION['book_filter'][$id]);
	}

	/*
	 * The database itself. This class stands for calibre's LibraryDatabase
	 * class. Much of the implementation is the same, but much isn't.
	 */

	class MySQLiteResultSet {

		function __construct($stmt) {
			$this->stmt = $stmt;
		}

		function __destruct() {
			if (isset($this->stmt)) {
				$this->stmt->closeCursor();
			}
		}

		function fetchArray($fetch_style=SQLITE3_ASSOC) {
			if ($fetch_style == SQLITE3_ASSOC)
				$ans = $this->stmt->fetch(PDO::FETCH_BOTH);
			else if ($fetch_style == SQLITE3_ASSOC)
				$ans = $this->stmt->fetch(PDO::FETCH_ASSOC);
			else
				$ans = $this->stmt->fetch(PDO::FETCH_NUM);
			return $ans;
		}
	}

	/*
	 * If PHP 5.2, use PDO. If 5.3, use SQLite3. The following class provides
	 * the SQLite3 when using PDO.
	 */
	class MySQLite {
		function open($path) {
			if (version_compare(phpversion(), '5.3', '<')) {
				$this->db = new PDO('sqlite:'.$path);
				define('SQLITE3_ASSOC', 0);
				define('SQLITE3_NUM', 1);
				define('SQLITE3_BOTH', 2);
				return $this;
			} else
				return new SQLite3($path, SQLITE3_OPEN_READONLY);
		}

		function createFunction($name, $func) {
			$this->db->sqliteCreateFunction($name, $func);
		}

		function querysingle($query, $entire_row = false) {
			$rs = $this->db->query($query);
			if ($entire_row) {
				$ans = $rs->fetch(PDO::FETCH_ASSOC);
				return $ans;
			}
			$ans = $rs->fetch(PDO::FETCH_NUM);
			return $ans[0];
		}

		function query($q) {
			$stmt = $this->db->prepare($q);
			$stmt->execute();
			if ($stmt)
				return new MySQLiteResultSet($stmt);
			return NULL;
		}
	}
	class CalDatabase {

		function __construct($libpath) {
			$this->libpath = $libpath;
			$db = new MySQLite();
			$this->db = $db->open($libpath.'/metadata.db');
			$this->db->createFunction('title_sort', 'title_sort');
			$this->db->createFunction('books_list_filter', 'books_list_filter');
			$fm = $this->db->querysingle("select val from preferences where key='field_metadata'");
			$this->fm = new FieldMetadata($fm);
			$this->CATEGORY_SORTS = array('name', 'popularity', 'rating');
			$uc_string = $this->db->querysingle("select val from preferences where key='user_categories'");
			$this->user_categories = json_decode($uc_string, true);
		}

		/*
		 * Return a list of categories. Used for debugging.
		 */
		function categories() {
			$res = array();
			foreach ($this->fm->fm_map() as $k => $v) {
				if ($v['is_category'])
					$res[] = "$k";
			}
			return $res;
		}

		function display_field($field) {
			global $config;

				// Skip fields in the 'not to do' list
			if ($config['fields_not_to_display'] &&
					($config['fields_not_to_display'] == '*' ||
					 in_array($field, $config['fields_not_to_display'])))
				return false;
			if ($config['fields_to_display'] &&
						($config['fields_to_display'] != '*' &&
						 !in_array($field, $config['fields_to_display'])))
				return false;
			return true;
		}

		function book_path($id) {
			$query = "SELECT path FROM books WHERE id=$id and books_list_filter(id)";
			$path = $this->db->querySingle($query, false);
			return $path;
		}

		function book_format_filename($id, $fmt) {
			$fmt = preg_replace('/\\W/', '', $fmt);
			$path = $this->db->querySingle(sprintf(
								'SELECT name
								 FROM data
								 WHERE book=%d and format = \'%s\'',
								 $id, $fmt));
			if ($path)
				return "$path." . strtolower($fmt);
			return NULL;
		}

		/*
		 * This is one of the work horses. It builds a map of
		 * 	category => item values
		 * where item values are category values such as a tag, not books.
		 * This method is capable of category sorting, should that appear in
		 * web UI some day.
		 */
		function get_categories($sort='name') {
			$categories = array();

			// First, build the standard and custom-column categories
			$keys = $this->fm->sorted_keys();
			foreach ($keys as $category) {
				if ($category == 'news' || ! $this->display_field($category))
					continue;

					$cat = $this->fm->metadata_for($category);

				if (! $cat['is_category'] or in_array($cat['kind'], array('user', 'search')))
					continue;
				$tn = $cat['table'];
				$categories[$category] = array();   #reserve the position in the ordered list
				if (! isset($tn))              		# Nothing to do for the moment
					continue;
				$cn = $cat['column'];
				$query = sprintf("SELECT id, %s, count, avg_rating, sort
							FROM tag_browser_filtered_%s", $cn, $tn);
				if ( $sort == 'popularity')
					$query .= ' ORDER BY count DESC, sort ASC';
				else if ($sort == 'name')
					$query .= ' ORDER BY sort ASC';
				else
					$query .= ' ORDER BY avg_rating DESC, sort ASC';
				$data = $this->db->query($query);

				$label = $this->fm->key_to_label($category);
				$datatype = $cat['datatype'];
				$avgr = create_function('$r', 'return $r[3];');
				$item_not_zero_func = create_function('$x', 'return $x[2] > 0;');

				if ($datatype == 'rating') {
					# eliminate the zero ratings line as well as count == 0
					$item_not_zero_func =
							create_function('$x',
											'return $x[1] > 0 and $x[2] > 0;');
					$formatter = create_function('$x', 'return ((int)($x/2));');
					$avgr = create_function('$r', 'return $r[1];');
				} else if ($category == 'authors')
					# Clean up the authors strings to human-readable form
					$formatter =
							create_function('$x',
											"return str_replace('|', ',', \$x);");
				else
					$formatter = create_function('$x', 'return $x;');

				$categories[$category] = array();
				while ($data && $r = $data->fetchArray()) {
					if (!$item_not_zero_func($r))
						continue;
					$categories[$category][] = new Tag($formatter($r[1]),
													$r['id'], $r['count'],
													$avgr($r), $r['sort'],
													$category);
				}
			}

			/* Needed for legacy databases that have multiple ratings that
			 * map to n stars. The bizarre loops account for PHP's handling of
			 * changed lists in the middle of a loop.
			 */
			while (1) {
				foreach ($categories['rating'] as $r) {
					foreach ($categories['rating'] as $k => $x) {
						if ($r->name == $x->name and $r->id != $x->id) {
							$r->count = $r->count + $x->count;
							unset($categories['rating'][$k]);
							continue 3;
						}
					}
				}
				break;
			}

			/* We delayed computing the standard formats category because it
			 * does not use a view, but is computed dynamically
			 */
			$categories['formats'] = array();
			$data = $this->db->query('SELECT DISTINCT format FROM data');
			while ($data and $fmt = $data->fetchArray()) {
				$fmt = $fmt[0];
				$count = $this->db->querySingle(
							sprintf('SELECT COUNT(id)
									FROM data
									WHERE format="%s" AND
										books_list_filter(book)', $fmt),
							false);
				if ($count > 0)
					$categories['formats'][] =
							new Tag($fmt, $fmt, $count, NULL, NULL, 'formats');
			}


			// Now do the user-defined categories.
			$user_categories = $this->user_categories;

			/*
			 * We want to use same node in the user category as in the source
			 * category. To do that, we need to find the original Tag node. There is
			 * a time/space tradeoff here. By converting the tags into a map, we can
			 * do the verification in the category loop much faster, at the cost of
			 * temporarily duplicating the categories lists.
			 */
			$taglist = array();
			foreach ($categories as $cat => $tags) {
				$taglist[$cat] = array();
				foreach ($tags as $tag) {
					$taglist[$cat][$tag->name] = $tag;
				}
			}

			$uc_keys = array_keys($user_categories);
			natcasesort($uc_keys);
			foreach ($uc_keys as $user_cat) {
				$items = array();
				foreach ($user_categories[$user_cat] as $cat) {
					if (array_key_exists($cat[1], $taglist) and
								array_key_exists($cat[0], $taglist[$cat[1]])) {
						$items[] = $taglist[$cat[1]][$cat[0]];
					}
					# else: do nothing, to not include nodes w zero counts
				}
				if (count($items) > 0) {
					$cat_name = $user_cat . ':'; # add the ':' to avoid name collision
					if ($sort == 'name')
						usort($items, create_function('$l,$r',
											'return strcasecmp($l->name, $r->name);'));
					else if ($sort == 'popularity')
						usort($items, create_function('$l,$r',
								'if ($l->count == $r->count) return 0;
								return ($l->count > $r->count) ? -1 : 1;'));
					else
						usort($items, create_function('$l,$r',
								'if ($l->avg_rating == $r->avg_rating) return 0;
								return ($l->avg_rating > $r->avg_rating) ? -1 : 1;'));
						$categories[$cat_name] = $items;
				}
			}
			return $categories;
		}

		/*
		 * Return all the metadata for a book. Also format the data for the
		 * template processor if requested.
		 */
		function book($id, $for_template) {
			global $config, $fullcomments;

			$query = "SELECT * FROM books WHERE id=$id and books_list_filter(id)";
			$book = $this->db->querySingle($query, true);
			$fm = $this->fm->fm_map();
			$keys = $this->fm->sorted_keys();

			// Add the normalized and custom fields to the book
			foreach ($keys as $k) {
				$m = $fm[$k];
				if ($m['datatype'] == 'composite')
					continue;
				$query = NULL;
				if ($m['is_category'] && isset($m['link_column']))
					// Normalized field
					if ($m['is_custom'] && $m['datatype'] == 'series')
						$query = sprintf("
							SELECT t.%s, l.extra FROM %s as t, books_%s_link as l
							WHERE t.id = l.%s and l.book=%d",
												$m['column'], $m['table'],
												$m['table'], $m['link_column'], $id);
					else
						$query = sprintf("
							SELECT t.%s FROM %s as t, books_%s_link as l
							WHERE t.id = l.%s and l.book=%d",
												$m['column'], $m['table'],
												$m['table'], $m['link_column'], $id);
				else if (isset($m['link_column']))
					// Unnormalized, but data in separate table
					$query = sprintf("
						SELECT t.%s FROM %s as t
						WHERE t.book = %d", $m['column'], $m['table'], $id);
				if ($query) {
					$data = $this->db->query($query);

					$res = array();
					while ($data && $row = $data->fetchArray()) {//SQLITE3_NUM)) {
						if ($k == 'authors')
							$res[] = trim(str_replace('|', ',', $row[0]));
						else if ($m['is_custom'] && $m['datatype'] == 'series') {
							$res[] = trim($row[0]);
							$book[$k . '_index'] = $row[1];
						} else
							$res[] = trim($row[0]);
					}
					if (!$res)
						continue;
					if (count($res) == 1 && $k != 'authors')
						if ($m['datatype'] == 'bool')
							$book[$k] = $res[0] ? true : false;
						else
							$book[$k] = $res[0];
					else
						$book[$k] = $res;
				}
			}

			// Formats
			$data = $this->db->query(sprintf('SELECT format,name
											  FROM data WHERE book=%d', $id));
			$fmts = array();
			while ($data and $row = $data->fetchArray()) {
				$fmt = $row['format'];
				$book_name = preg_replace('/\\W/', '_', $row['name']);
				$book_name = urlencode("$book_name." . strtolower($fmt));
				$fmts[] = array
					('format' => $row['format'],
					 'name' => $row['name'],
					 'URL' => "index.php/$book_name?m=book_format&id=$book[id]&fmt=$fmt");
			}
			$book['formats'] = $fmts;

			// Comments
			$query = "SELECT text FROM comments WHERE comments.book = $id";
			$data = $this->db->querySingle($query, false);
			if($fullcomments)
				$book['comments'] = strip_tags($data, '<p><br><em><i><strong><b>');
			else
				$book['comments'] = strip_tags(substr($data,0, 500), '<p><br><em><i><strong><b>');

			// Cover -- let the cover module work out what to do if the cover
			// doesn't exist
			$book['cover'] = $book['path'] . '/cover.jpg';

			if (!$for_template)
				return $book;

			$book['field_names'] = array();
			$book['field_values'] = array();
			$book['custom_comments_names'] = array();
			$book['custom_comments_values'] = array();

			// Author has its own field
			$book['field_authors'] = implode(', ', $book['authors']);

			// Create the arrays for Smarty
			foreach ($keys as $k) {
				$m = $fm[$k];

				if ($k == 'rating' && isset($book[$k]) && $book[$k] > 0)
					$book['rating_url'] = "index.php?m=rating&r=" . round($book[$k]/2, 2);

				// Skip fields already done.
				if (!isset($book[$k]) || !$m['name'] ||
							in_array($k, array( 'comments', 'formats', 'title',
												'authors', 'sort', 'rating')))
					continue;

				// Apply the display/don't display spec
				if (!$this->display_field($k))
					continue;

				if ($m['datatype'] == 'series') {
					// Format series indices
					$book['field_names'][] = $m['name'];
					if (isset($book[$k . '_index']))
						$book['field_values'][] = $book[$k] .
											' [' . $book[$k . '_index'] . ']';
					else
						$book['field_values'][] = $book[$k];
				} else if ($m['is_custom']) {
					// Format various custom field datatypes
					if ($m['datatype'] == 'comments' && $book[$k]) {
						$book['custom_comments_names'][] = $m['name'];
						if($fullcomments)
							$book['custom_comments_values'][] = strip_tags($book[$k], '<p><br><em><i><strong><b>');
						else
							$book['custom_comments_values'][] = strip_tags(substr($book[$k],0, 300), '<p><br><em><i><strong><b>');
					} else if ($m['datatype'] == 'datetime') {
						$book['field_names'][] = $m['name'];
						$book['field_values'][] =
							format_date($book[$k],	$m['display']['date_format']);
					} else if ($m['datatype'] == 'bool') {
						$book['field_names'][] = $m['name'];
						$book['field_values'][] = $book[$k] ? 'Yes' : 'No';
					} else {
						$book['field_names'][] = $m['name'];
						$book['field_values'][] = $book[$k];
					}
				} else if ($k == 'rating') {
					$book['rating_url'] = "index.php?m=rating&r=" . round($book[$k]/2, 2);
					$book['field_names'][] = 'Rating';
					$book['field_values'][] = $book[$k]/2;
				} else if ($k == 'timestamp') {
					$book['field_names'][] = $m['name'];
					$book['field_values'][] =
								format_date($book[$k], $config['timestamp_format']);
				} else if ($k == 'pubdate') {
					$book['field_names'][] = $m['name'];
					$book['field_values'][] =
								format_date($book[$k], $config['pubdate_format']);
				} else {
					/*
					 *  A field not otherwise mentioned. If the field is an
					 *  array, then turn it into a comma-separated string.
					 */
					$book['field_names'][] = $m['name'];
					if (is_array($book[$k]))
						$book['field_values'][] = implode(', ', $book[$k]);
					else
						$book['field_values'][] = $book[$k];
				}
			}

			return $book;
		}

		/*
		 * Return the books in the given category. This function assumes that
		 * get_categories has been called before, and that the filter is
		 * set correctly.
		 */
		function books_in_category($category, $id) {
			if ($category == 'formats') {
				$query = sprintf("
								SELECT b.id FROM books as b, data as d
								WHERE 	d.format='%s' and b.id = d.book and
										books_list_filter(b.id)
								ORDER BY b.sort", $id);
			} else {
				$meta = $this->fm->metadata_for($category);
				if (!isset($meta))
					return 'missing field metadata';
				$query = sprintf("SELECT books.id FROM books, books_%s_link as l
								  WHERE l.%s=%d and books.id = l.book and
										books_list_filter(books.id)
								  ORDER BY books.sort",
								  $meta['table'], $meta['link_column'], $id);
			}

			$data = $this->db->query($query);
			$books = array();
			while ($data and $row = $data->fetchArray()) {
				$book = $this->book($row['id'], true);
				$books[] = $book;
			}
			return $books;
		}

		/*
		 * Return all books in the database, respecting the search
		 */
		function all_books($for_template, $random=FALSE, $id=FALSE) {
			if($random)
				$query = "SELECT id FROM books WHERE books_list_filter(id) ORDER BY RANDOM() LIMIT 50;";
			else
				$query = "SELECT id FROM books WHERE books_list_filter(id) ORDER BY sort";

			$books = array();
			if($id==FALSE){				
				$data = $this->db->query($query);
				while ($data and $row = $data->fetchArray(SQLITE3_ASSOC)) {
					$book = $this->book($row['id'], $for_template);
					$books[] = $book;
				}
			}else{
				$book = $this->book($id, $for_template);
				$books[] = $book;
			}
			return $books;
		}

		/*
		 * Return the count of books in the database, respecting the search
		 */
		function all_books_count() {
			$query = "SELECT count(id) FROM books WHERE books_list_filter(id) ORDER BY sort";
			return $this->db->querySingle($query, false);
		}

		/*
		 * Simple search, similar to what is offered by calibre.
		 * The results of the search are stored in the session so that they
		 * are available in the future. Note that doing this means that if the
		 * DB changes, the search can be rendered invalid. Note also that
		 * the restriction is added to the query.
		 */
		function search($query_string) {
			global $config;

			$_SESSION['book_filter'] = NULL;

			$query = new Query(restriction_to_apply(), $query_string, $this->fm);
			$_SESSION['search_error'] = $query->error_message;

			if ($query->is_empty())
				return;

			$books = $this->all_books(false);
			$_SESSION['book_filter'] = array();

			// Loop through all books
			foreach ($books as $book) {
				// Start the query evaluator
				$query->prepare();
				// Evaluate the fields of each book
				$query->eval_fields($book);
				// now evaluate the expression
				if ($query->evaluate())
					$_SESSION['book_filter'][$book['id']] = true;
			}
		}
	}
?>
