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
	 * Produce an image of 5 stars, grey scaled from R to 5.
	 */

	class DoRating extends Module {

		function check_arguments($db) {
			return false;
		}

		function do_work($smarty, $db) {
			global $config;

			$p = $config['web_dir'] . '/images/five_stars.png';
			if (isset($_REQUEST['r']) && is_numeric($_REQUEST['r']))
				$r = (float)($_REQUEST['r']);
			else
				// default: zero rating
				$r = 0;

			header ('Content-type: image/png');
			list($width, $height) = getimagesize($p);
			// compute the length of the colored portion
			$w = (int)(($width/5) * $r);
			$image = imagecreatefrompng($p);
			$image_p = imagecreatefrompng($p);
			// convert to grey from the end of the 'rating' to the image end.
			imagecopymergegray($image, $image_p, $w, 0, $w, 0, $width-$w, $height, 20);
			imagepng($image, NULL, 9);
			imagedestroy($image);
			imagedestroy($image_p);
		}

		function template() {
			return NULL;
		}

	}

	$mod = new DoRating();
?>