<?php
	/*
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'module.php';
	require_once 'utilities.php';
	require_once 'config.php';
	require_once 'mimetypes.php';

	/*
	 * The handler that delivers a book format (epub, mobi, etc.)
	 */
	class DoBookFormat extends Module {

		function check_arguments($db) {
			/*
			 *  None of these error messages will go anywhere, because the
			 *  browser is expecting a book, not an html page.
			 */
			if (!isset($_REQUEST['id']))
				return "Missing book id argument 'id' in query string";
			$id = $_REQUEST['id'];
			$path = $db->book_path($id);
			if (! $path)
				return "invalid ID $id";
			if (!($path = case_insensitive_path($db->libpath, $path)))
				return "no such book $path";
			if (!isset($_REQUEST['fmt']))
				return "Missing book format argument 'fmt' in query string";
			$fmt = $_REQUEST['fmt'];
			if (!($path = case_insensitive_path($path,
										$db->book_format_filename($id, $fmt))))
				return "no such book format $path $fmt";
			$this->path = $path;
			return false;
		}

		function do_work($smarty, $db) {
			global $config, $mimetypes;

			$path = $this->path;
			$pathinfo = pathinfo($path);

			$mt = NULL;
			// get the mimetype of the format to be downloaded
			if (isset($pathinfo['extension'])) {
				$ext = strtolower($pathinfo['extension']);
				if (isset($mimetypes[$ext]))
					$mt = $mimetypes[$ext];
			}
			if (!$mt) {
				// mimetype unknown. Ask PHP for one.
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				$mt = finfo_file($finfo, $path);
			}
			header ('Content-type: ' . $mt);
			readfile($path);
		}

		function template() {
			return NULL;
		}
	}

	$mod = new DoBookFormat();
?>