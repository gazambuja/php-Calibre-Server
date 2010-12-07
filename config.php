<?php
	/*
		Name:		 Calibre PHP webserver
		license:	 GPL v3
		copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
	*/

	require_once 'config_default.php';
	if (file_exists('config_local.php'))
		require_once 'config_local.php';
?>