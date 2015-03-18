<?php

namespace Conjecto\EasyRdfBundle\Controller;

use Conjecto\EasyRdfBundle\Form\PersonType;
use Conjecto\EasyRdfBundle\RdfResource\Person;
use Conjecto\RAL\QueryBuilder\QueryBuilder;
use Conjecto\RAL\ResourceManager\Resource\Resource;
use EasyRdf\Collection;
use Elastica\Document;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class
DefaultController extends Controller
{
    /**
     * @Route("/all/", name="person.index")
     * @Template("EasyRdfBundle:Default:index.html.twig")
     */
    public function indexAction()
    {
        /** @var QueryBuilder $qb */
        //$qb = $this->get('rm')->getQueryBuilder();
        //$qb->selectAll()->where("?s a foaf:Person");
        //$res = $qb->getQuery()->execute();

        $this->container->get('ral.elasticsearch_type.ogbd.person')->addDocument(new Document('coin',array('pif'=>'paf')));
        /** @var Collection $test */
        $test = $this->container->get('rm')->getRepository('ogbd:Notaire')->findBy(array(), array('orderBy' => "ogbd:nom", 'limit' => 100));
        //$test = $this->container->get('rm')->getRepository('foaf:Person')->findAll();

        $results = array();

        $te = $test->get('rdf:first');
        $test = $test->get('rdf:rest');
        $cnt = 0 ;

        /** @var Resource $add */
        while ($te ) {
            $results[] = array("name" =>$te->get("ogbd:nom"), "uri" =>urlencode($te->getUri()));
//            /** @var Resource $add */
            if (false) {
                /** @var Resource $add */
                $add = $this->get('rm')->getRepository('vcard:Address')->create();
                $te->delete("vcard:hasAddress");
                $add->set("vcard:locality", "WWHonek");
                $te->set("vcard:hasAddress", $add);
            }

            $te = $test->get('rdf:first');
            $test = $test->get('rdf:rest');
        }


        return array('list' => $results);
    }

    /**
     * @Route("/view/{uri}", name="person.view", requirements={"uri" = ".+"})
     * @Template("EasyRdfBundle:Default:view.html.twig")
     */
    public function viewAction(Request $request, $uri)
    {
        $res = $this->container->get('rm')->getRepository('ogbd:Genealogiste')->find($uri);

        var_dump($res);
        if ($res == null) {
            return new Response('not found', 404);
        }
        //$res->get('vcard:hasAddress/vcard:locality');
        $form = $this->createForm('resource_person', $res);

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->get('rm')->flush();
                return $this->redirect($this->generateUrl('person.index'));
            }
        }

        return array(
            'form'  => $form->createView(),
            'user'  => array(
                "uri"   => $uri,
                "name"  => $res->get('ogbd:nom'),
                "place" => $res->get('vcard:hasAddress/vcard:locality')
            )
        );
    }

    /**
     * @Route("/delete/{uri}", name="person.delete", requirements={"uri" = ".+"})
     * @Template("EasyRdfBundle:Default:delete.html.twig")
     */
    public function deleteAction(Request $request, $uri)
    {
        $res = $this->container->get('rm')->getRepository('foaf:Person')->find($uri);

        if ($request->getMethod() == "POST") {
            $this->container->get('rm')->remove($res);
            $this->container->get('rm')->flush();
            //return $this->redirect($this->generateUrl('person.index'));
        }

        return array('user'=> array("uri" => $uri, "name" => $res->get('foaf:givenName'), "place" => $res->get('vcard:hasAddress/vcard:locality')));
    }

    /**
     * @Route("/create/", name="person.create")
     * @Template("EasyRdfBundle:Default:create.html.twig")
     */
    public function createAction(Request $request)
    {
        /** @var Person $person */
        $person = $this->container->get('rm')->create('foaf:Person');
        $form = $this->createForm('resource_person', $person);

        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);

            $this->get('rm')->persist($person);
           // var_dump($person->get('vcard:hasAddress'));
           // print_r($person->getGraph()->resource('_:bn2')->getGraph()->toRdfPhp());
            $this->get('rm')->flush();

            return $this->redirect($this->generateUrl('person.index'));
        }

        return array("form" => $form->createView());
    }
}
