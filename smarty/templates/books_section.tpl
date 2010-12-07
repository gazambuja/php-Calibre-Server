{{*<!--
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley <charles@haleys.org
-->*}}
	<table>
	{{section name=book loop=$books}}
		<tr>
			<td width="150px" valign="top">
				<a class='book_download' href="?m=titles&id={{$books[book].id}}" title="{{$books[book].title}} por {{$books[book].field_authors}}">
					<img src="{{$books[book].cover}}">
				</a>
			</td>
			<td valign="top" width="20%">
				<div class="book_title">{{$books[book].title}}</div>
				<div class="book_authors">{{$books[book].field_authors}}</div>
				{{if $books[book].rating_url}}<img style="vertical-align: middle" src="{{$books[book].rating_url}}"><br />{{/if}}
				{{section name=format loop=$books[book].formats}}
					<a class='book_download' href="{{$books[book].formats[format].URL}}" title="Bajar en formato: {{$books[book].formats[format].format}}">BAJAR LIBRO</a> 
				{{/section}}
			</td>
			<td valign="top" width="30%" class='metadata'>
				{{section name=field loop=$books[book].field_names}}
					{{$books[book].field_names[field]}}: {{$books[book].field_values[field]}}<br>
				{{/section}}
			</td>
			<td valign="top" width="40%" class='notes'>
				{{if $books[book].comments != ''}}{{$books[book].comments}}<br>{{/if}}
				{{section name=cust loop=$books[book].custom_comments_names}}
					{{$books[book].custom_comments_names[cust]}}:{{$books[book].custom_comments_values[cust]}}
				{{/section}}
			</td>
		<tr>
			<td colspan="4"><hr></td>
		</tr>
		</tr>
	{{/section}}
	</table>
