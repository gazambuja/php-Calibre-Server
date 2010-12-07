{{*<!--
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley <charles@haleys.org
-->*}}
{{include file="header.tpl" title="Inicio"}}

	<ul class='list'>
		<li><a href="index.php?m=titles&p=1"><img src="images/book.png" border="0"><span>Libros</span></a>
			[{{$title_count}}]
		</li>
		<li><a href="index.php?m=random"><img src="images/random-icon.png" border="0"><span>Libros Aleatórios</span></a>
		</li>
		{{section loop=$categories name=cat}}
		<li><a href="{{$categories[cat].href}}"><img src="{{$categories[cat].icon}}" border="0"><span>{{$categories[cat].name}}</span></a>
			[{{$categories[cat].count}}]
		</li>
		{{/section}}
	</ul>

{{include file='footer.tpl' foo='bar'}}
