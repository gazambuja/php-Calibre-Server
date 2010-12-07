<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	$config = array();

	/*
	 * The title that appears at the top of every page in the default template
	 */
	$config['page_title'] = 'Calibre Server';

	/*
	 * The directory containing calibre's metadata.db file, with sub-directories
	 * containing all the formats.
	 */
	$config['library_dir'] = '';

	/*
	 * The directory containing the PHP code.
	 */
	$config['web_dir'] = '';

	/*
	 * The directory in web space where smarty will find the templates.
	 */
	$config['smarty_web_dir'] = $config['web_dir'] . '/smarty';

	/*
	 * The directory where smarty is to store its caches and the like. The web
	 * server must have write access here.
	 */
	$config['smarty_dir'] = '';

	/*
	 * The directory containing the Smarty PHP files, and in particular the file
	 * Smarty.class.php.
	 */
	$config['smarty'] = 'some path goes here';

	/*
	 * The maximum width of a cover. A cover's aspect ratio is preserved, so
	 * one of width or height will win.
	 */
	$config['cover_max_width'] = 100;
	$config['cover_max_height'] = 100;

	/*
	 * The maximum number of books appearing on a page.
	 */
	$config['books_page_count'] = 20;

	/*
	 * The format of the publication date. Use the same format strings as
	 * calibre's GUI.
	 */
	$config['pubdate_format'] = 'dd-MMM-yyyy';

	/*
	 * The format of the timestamp, which is called 'date' in calibre.
	 */
	$config['timestamp_format'] = 'dd-MMM-yyyy';

	/*
	 * The list of fields, custom or otherwise, to display in the information
	 * column of a book and in the categories pages. An entry of '*' means
	 * all fields. A value of '' means no fields. A value of
	 * 		array('foo', 'bar', 'pubdate')
	 * means the three fields named.
	 */
	$config['fields_to_display'] = '*';

	/*
	 * The list of fields, custom or otherwise, not to display in the information
	 * column of a book and in the categories pages. Entries as in
	 * 'fields_to_display'. The 'not_to_display' filter is applied first.
	 */
	$config['fields_not_to_display'] = '';

	/*
	 * A search that books must match to be displayed. This is a restriction
	 * in calibre terms. If the restriction is a string, then it is applied to
	 * all accesses. If it is an array, then the keys are usernames, and the
	 * appropriate restriction is applied based on the user. In this case, the
	 * user key '*' is the default. Example:
	 * $config['restrict_display_to'] = array('bill'=>'series:1632', '*'=>'');
	 * which says that bill can see only the books in the 1632 series, while
	 * everyone else has no restrictions.
	 */
	$config['restrict_display_to'] = '';
?>
