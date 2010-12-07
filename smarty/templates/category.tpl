{{*<!--
		Name:		 Calibre PHP webserver
		License:	 GPL v3
		Copyright:	 2010, Charles Haley <charles@haleys.org
-->*}}
{{include file="header.tpl" title="Categories"}}
<table>
{{section loop=$categories name=cat}}
<tr style="padding-right: 20px; font-family: 'Arial Rounded MT Bold';">
<td>
	<a href="{{$categories[cat].href}}">
	<img style="padding: 2px; vertical-align: middle" src="{{$categories[cat].icon}}" border="0"></a>
	<span style="padding-left:3px">[{{$categories[cat].count}}]</span>
</td>
<td>
	<a href="{{$categories[cat].href}}">{{$categories[cat].name}}</a>
</td>
<td>
	<span style="padding-left:10px">{{if $categories[cat].rating}}<img style="vertical-align: middle" src="{{$categories[cat].rating}}">{{/if}}</span>
</td>
</tr>
{{/section}}
</table>
{{include file="footer.tpl"}}