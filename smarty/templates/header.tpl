{{*<!--
        Name:		 Calibre PHP webserver
        License:	 GPL v3
        Copyright:	 2010, Charles Haley <charles@haleys.org
-->*}}
<html>

<head>
<meta content="en-us" http-equiv="Content-Language">
<title>{{$title}}</title>
<meta content="text/html; charset=utf-8" http-equiv="Content-Type">
<link rel="stylesheet" type="text/css" href="style.css" />
</head>

<body>
	<div class='header'>
		<div class='title'>
			<a href="index.php">{{$page_title}}</a> | <small>{{$title}}</small>
		</div>
		<div class='search'>
			<form action="index.php" method="GET" >
				<input name="query" type="text" value='{{$last_search}}'>
				<input name="search" type="submit" value="Buscar">
				<input type="hidden" name="m" value="search">
			</form>
		</div>
	</div>
	
	<div class='divider'></div>
	
	{{if $page}}
		<div id='pager' class='pager'>
			<form action="index.php" method="get" >
					{{if $page_back}}<a href="{{$page_back}}">anterior</a>{{else}}anterior{{/if}}
					&nbsp;&nbsp;&nbsp;Página <input name="p" type="text" size="1" value="{{$page}}">
					<input name="gotopage" type="submit" value=" Ir "> de {{$maxpage}}&nbsp;&nbsp;&nbsp;
					{{if $page_forw}}<a href="{{$page_forw}}">siguiente</a>{{else}}next{{/if}}            
					<input type="hidden" name="m" value="titles">
			</form>
		</div>
	{{/if}}
