Nemrod 0.1.1
============

* More docs
* Form component: Better empty_data management
* Form component: Literal (or subclass) is instantiated if appropriate
* Resource manager: Datatypes + langs (for strings) are managed in persister
* Resource manager: Resources are snapshoted at first change in graph instead of at loading time (performances gain)
* Resource manager: Repositoty's findOneBy now based on SPARQL subquery + returns a Resource
* Resource manager: resources uri are managed on their expanded form
* Resource manager: a lifecycle listener/subscriber can be registered to ALL (endpoints) managers events at once.
* EasyRdf's Base resource switching abilities are used; Nemrod base resource is default base class in framework

Nemrod 0.1.0
=============

* First public release
* ResourceManager supporting SPARQL Endpoints
* QueryBuilder
* Form component
* JsonLD Serialization
