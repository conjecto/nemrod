Indexing your data using ElasticSearch
=====

Configuration
------------

Nemrod permits to index rdf resources.
First, you have to fill-in the elastica configuration in your config.yml like this :

elastica:
  clients:
    default:
      host: %elasticsearch_host%
      port: %elasticsearch_port%
  indexes:
    indexname:
      client: default
      types:
        resource:
          type: "ex:User"
          frame: '@User/user-es.jsonld'

You can specify several indexes and several resources by index.
The frame value is the path to a file wich contains a JsonLd frame. With this path, the file can be found at UserBundle/Resources/frames/user-es.jsonld.

The content of this file could be:
{
  "@context": {
    "vcard": "http://www.w3.org/2006/vcard/ns",
    "foaf": "http://xmlns.com/foaf/0.1/"
  },
  "@explicit": "true",
  "@embed": "true",
  "@type": "ex:User",
  "foaf:name": {
    "@default": "",
    "@mapping": {
      "type": "string",
      "index": "not_analyzed"
    }
  },
  "faof:mbox": {
      "@default": "",
      "@mapping": {
        "type": "string",
        "index": "not_analyzed"
      }
    },
  "vcard:hasAddress": {
    "@type": "ex:Place",
    "@explicit": "true",
    "@embed": "true",
    "@default": null,
    "@mapping": {
      "type": "object",
      "index": "not_analyzed"
    },
    "vcard:street-address": {
      "@default": "",
      "@mapping": {
        "type": "string",
        "index": "not_analyzed"
      }
    },
    "vcard:locality": {
      "@default": "",
      "@mapping": {
        "type": "string",
        "index": "not_analyzed"
      }
    },
    "vcard:postal-code": {
      "@default": "",
      "@mapping": {
        "type": "string",
        "index": "not_analyzed"
      }
    }
  }
}

If a resource has a property specified in the frame wich is not filled-in in the triple store, the resource won't be updated in elasticsearch if there is no @default for this property in the frame.

You can use the commands nemrod:elastica:reset  and nemrod:elastica:populate to reset or populate indexes.

Finally, you can use the service nemrod.elastica.search to search documents.