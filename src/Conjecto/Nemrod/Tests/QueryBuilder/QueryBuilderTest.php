<?php


namespace Conjecto\Nemrod\Tests\QueryBuilder;

use Conjecto\Nemrod\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testParameters()
    {
        $qb = new QueryBuilder(null);
        $qb->construct()
          ->where("#uri a ogbd:Interlocuteur")
          ->setParameter("uri", "<http://urn/foo>");

        var_dump($qb->getSparqlQuery());
    }
}
