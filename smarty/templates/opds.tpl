<?xml version='1.0' encoding='utf-8'?>
<feed xmlns:dc="http://purl.org/dc/terms/" xmlns:opds="http://opds-spec.org/2010/catalog" xmlns="http://www.w3.org/2005/Atom">
 <title>Biblioteca de la CdZ</title>
 <author>
  <name>calibre</name>
  <uri>http://calibre-ebook.com</uri>
 </author>
 <id>calibre-all:timestamp</id>
 <updated>2010-12-03T11:12:06+00:00</updated>
 <link href="?m=search&search=Buscar&query={searchTerms}" type="application/atom+xml" rel="search" title="Buscar"/>
 <link href="?m=opds" type="application/atom+xml;type=feed;profile=opds-catalog" rel="start" title="Inicio"/>
 <link href="?m=opds&p=2" type="application/atom+xml;type=feed;profile=opds-catalog" rel="last"/>
 <link href="?m=opds&p=3" type="application/atom+xml;type=feed;profile=opds-catalog" rel="next" title="Siguiente"/>
{{section name=book loop=$books}}
 <entry>
  <title>{{$books[book].title}}</title>
  <author>
   <name>{{$books[book].field_authors}}</name>
  </author>
  <id>urn:uuid:bbdfe7ac-7504-4218-b6bc-d4d52f9b5732</id>
  <updated>2010-12-03T11:12:06+00:00</updated>
  <content type="xhtml">
  {{section name=field loop=$books[book].field_names}}
   <div xmlns="http://www.w3.org/1999/xhtml">{{$books[book].field_names[field]}}<br/><p class="description">{{$books[book].field_values[field]}}</p></div>
  {{/section}}
  </content>
  {{section name=format loop=$books[book].formats}}<link href="{{$books[book].formats[format].URL}}" type="application/epub+zip" rel="http://opds-spec.org/acquisition"/>{{/section}}
  <link href="{{$books[book].cover}}" type="image/jpeg" rel="http://opds-spec.org/cover"/>
 </entry>
{{/section}}
</feed>
