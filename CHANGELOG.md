## [Unreleased]
### Changed
- New changelog format (http://keepachangelog.com)
- New error mapping mechanism in ResourceFormType, to use Validation assertions in Resource

### Fixed
- PropertyMetaAccessor renamed ResourcePropertyAccessor and now rely on __get and __set
- New __get and __set methods for Resource, using mapped properties

## Nemrod 0.1.3

* Nemrod now index resources in elasticsearch
* RDF resources are cascade updated and deleted in elasticsearch
* Elasticsearch indexed documents use polymorphism. You can search a document with all its rdf types 
* Better JsonLD serialization
* A lot of bug fixes

## Nemrod 0.1.2

* Resource manager: finf(One)By manages datatypes & langs (through EasyRdf Literals), uris.

## Nemrod 0.1.1

* Form component: Better empty_data management
* Form component: Literal (or subclass) is instantiated if appropriate
* Resource manager: Datatypes + langs (for strings) are managed in persister
* Resource manager: Resources are snapshoted at first change in graph instead of at loading time (performances gain)
* Resource manager: Repositoty's findOneBy now based on SPARQL subquery + returns a Resource
* Resource manager: resources uri are managed on their expanded form
* Resource manager: a lifecycle listener/subscriber can be registered to ALL (endpoints) managers events at once.
* EasyRdf's Base resource switching abilities are used; Nemrod base resource is default base class in framework

## Nemrod 0.1.0

* First public release
* ResourceManager supporting SPARQL Endpoints
* QueryBuilder
* Form component
* JsonLD Serialization

