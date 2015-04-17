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

The resource frame
------------
Secondly, you have to fill-in a JsonLd frame for the resources. You can use the same frame for multiple resources.

The frame value in the config is the path to a file which contains the JsonLd frame. With the specified value path '@User/user-es.jsonld', the file can be found at UserBundle/Resources/frames/user-es.jsonld.

The content of this file could be:

    {
      "@context": {
        "vcard": "http://www.w3.org/2006/vcard/ns",
        "foaf": "http://xmlns.com/foaf/0.1/"
      },
      "@explicit": "true",
      "@embed": "true",
      "@type": "foaf:Person",
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

If a resource has a property specified in the frame which is not filled-in in the triple store, the resource won't be updated in elasticsearch if there is no @default for this property in the frame.

Note that for the @context frame part, Nemrod concatenates the nemrod:namespaces part in config.yml with the frame context. Accordingly, in this example, if you have specified the 
vcard and the foaf prefixes in config.yml, it is not necessary to specify these a second time in the @context part in this frame.

The elasticsearch mapping is specified in the frame with the property @mapping.

Additional tools
------------
You can use the commands nemrod:elastica:reset and nemrod:elastica:populate to reset or populate indexes.

Finally, you can use the service nemrod.elastica.search.foaf.person to search documents of foaf person resources on elasticsearch.
