{{*<!--
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley <charles@haleys.org
-->*}}
	<div class='clearboth'></div>
	
	<ul class='estantes'>
	{{section name=book loop=$books}}
		<li class='cover'>
			<a class='book_download' href="?m=titles&id={{$books[book].id}}" title="{{$books[book].title}} por {{$books[book].field_authors}}">
			 <img src="{{$books[book].cover}}">
			</a>
		</li>
	{{/section}}
	</li>

	<div class='clearboth'></div>
