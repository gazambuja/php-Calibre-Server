<?php
    /*
        Name:		 Calibre PHP webserver
        license:	 GPL v3
        copyright:	 2010, Charles Haley
	                 http://charles.haleys.org
    */

    require_once 'config.php';
    require_once $config['smarty'] . '/Smarty.class.php';

    $smarty = new Smarty;
    
    /*
     * Initialize smarty, giving it the various locations.
     */

    $smarty->template_dir =  $config['smarty_web_dir'] . '/templates';
    $smarty->config_dir = $config['smarty_web_dir'] . '/config';
    $smarty->cache_dir = $config['smarty_dir'] . '/smarty_cache';
    $smarty->compile_dir = $config['smarty_dir'] . '/smarty_templates_c';
    
    /*
     * Change the delimiters to avoid conflicts with style declarations.
     */
    $smarty->left_delimiter = '{{';
    $smarty->right_delimiter = '}}';
?>