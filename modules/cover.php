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

	/*
	 * Handler to produce cover images
	 */

	class DoCover extends Module {

		function check_arguments($db) {
			global $config;
			/*
			 *  None of these error messages will go anywhere, because the
			 *  browser is expecting an image, not an html page.
			 */
			if (!isset($_REQUEST['id']))
				return "Missing book id argument 'id' in query string";
			$id = $_REQUEST['id'];
			$path = $db->book_path($id);
			if (! $path)
				return "invalid ID $id";
			if (!($path = case_insensitive_path($db->libpath, $path)))
				return "no such book $path";
			/* Give a default cover if the file isn't there. */
			$path = "$path/cover.jpg";
			if (!file_exists($path))
				$path = $config['web_dir'] . '/images/default_cover.jpg';
			$this->path = $path;
			return false;
		}

		function do_work($smarty, $db, $height=FALSE, $width=FALSE) {
			global $config;

			$p = $this->path;
			header ('Content-type: image/jpeg');

			/*
			 * Scale the cover to fit in the configured bounding box. Preserve
			 * aspect ratios.
			 */
			if($height==FALSE & $width==FALSE){
				$width =  $config['cover_max_width'];
				$height = $config['cover_max_height'];
			}

			// Get new dimensions
			if (!$image = @imagecreatefromjpeg($p)) {
				// failed to load image. Abandon ship.
				return;
			}
			// Get existing dimensions
			list($width_orig, $height_orig) = getimagesize($p);

			// Scale the new width or height to the aspect ratio of the original.
			$ratio_orig = $width_orig/$height_orig;
			if ($width/$height > $ratio_orig)
				$width = $height*$ratio_orig;
			else
				$height = $width/$ratio_orig;

			// Resample
			$image_p = imagecreatetruecolor($width, $height);
			imagecopyresampled($image_p, $image, 0, 0, 0, 0,
							$width, $height, $width_orig, $height_orig);

			// send the image
			imagejpeg($image_p);
			imagedestroy($image);
			imagedestroy($image_p);
		}

		function template() {
			return NULL;
		}
	}

	$mod = new DoCover();
?>
