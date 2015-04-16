Using the Query Builder
====

How to get the query builder
------------
You can get a query builder to help you to creates sparql queries.
To get one, you have to call it from to resource manager like this :

    $qb = $container->get('rm')->getQueryBuilder();

You can also get a query builder preconfigured for a specific repository like this :

    $qb = $container->get('rm')->getRepository('foaf:Person')->getQueryBuilder();

You can use this query builder to make select, construct, describe, ask, delete, insert and delete-insert queries.

How to use the query builder
------------
You can use the query builder like the doctrine query builder. The difference is that some functions change.

Some examples :

    use Conjecto\Nemrod\QueryBuilder;

    $qb->construct('?uri a foaf:Person')
       ->addConstruct('?uri vcard:hasAddress ?o')
       ->where('?uri a foaf:Person')
       ->andWhere('?uri vcard:hasAddress ?o')
       ->bind("<uri>", '?uri');

    $qb->select('rdf:type')
       ->addSelect('vcard:hasAddress')
       ->where('?uri a foaf:Person')
       ->bind("<uri>", '?uri');

    $qb->ask('?uri a foaf:Person')
       ->bind("<uri>", '?uri');

    $qb->describe('foaf:Person');

You can also use the repository to get resources.

    $container->get('rm')->getRepository('foaf:Person')->find($uri);
    $container->get('rm')->getRepository('foaf:Person')->findAll();
    $container->get('rm')->getRepository('foaf:Person')->findBy(array(...));
    $container->get('rm')->getRepository('foaf:Person')->findOneBy(array(...));

Execute a query
------------
When your query builder is ready, juste call 

    $result = $qb->getQuery()->execute();

You can use hydratators to hydrate results. You can hydrate the result as a array or a easyrdf collection.

To do this, specify the hydrate parameter like follow:

    use use Conjecto\Nemrod\QueryBuilder\Query;
    
    $qb->getQuery()->execute(Query::HYDRATE_COLLECTION);
    or
    $qb->getQuery()->execute(Query::HYDRATE_ARRAY);

