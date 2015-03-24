README
======

What is Nemrod?
-----------------
Nemrod is a framework providing an abstraction layer for handling (consuming and producing) RDF in a Symfony2 project, in the same way Symfony users are used
to do using Doctrine. The framework provides five main components :

- a resource manager (similar to Doctrine's resource manager)
- a SPARQL query builder allowing to interact with a sparql endpoint
- a form extension allowing to build forms to create or update data
- a json-ld serializer allowing to produce framed json-ld representation of RDF
- Optionally, a set of services can be set up that populates and update an Elastic Search server wrt triple store content.

 Nemrod mainly relies on
  
- EasyRdf
- ml/json-ld
- Elastica
- jms/serializer-bundle



Requirements
------------

Installation
------------
Nemrod can be installed using composer :

    composer require conjecto/nemrod

you can also add dependency directly in your composer.json file:

    "conjecto/nemrod": "~1.0@dev"

Then you need to add one (or two) bundle(s) to the AppKernel.php:

	class AppKernel extends Kernel
	{
    	public function registerBundles()
    	{
        	$bundles = array(
				...
			    new Conjecto\Nemrod\Bundle\NemrodBundle\NemrodBundle(),
    			new Conjecto\Nemrod\Bundle\ElasticaBundle\ElasticaBundle(),
				...
			);
		}
	}

The first bundle is the main framework bundle, the second should be enabled only if you wish to use an ElasticSearch server.

A little bit of configuration is necessary to let Nemrod know one thing or two about your environment:

	nemrod:
	  endpoints:
        my_endpoint: "http://www.foo.org/sparql"
    	another_endpoint: "http://www.bar.net/sparql"
  	  default_endpoint: my_endpoint
  	  namespaces:
        rdfs: "http://www.w3.org/2000/01/rdf-schema#"
        foaf: "http://xmlns.com/foaf/0.1/"
        #you can of course add your own namespaces
		mycompany: "http://www.example.org/"

At this point, Nemrod knows enough to access to your data. Use the 'rm' service's `findAll()` (which is an alias for 'nemrod.resource\_manager.my\_endpoint'):

	<?php
	
	use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
	use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

	class ProductsController extends Controller
	{
    	/**
     	 * @Route("/all/", name="person.index")
     	 * @Template("ProductsBundle:index.html.twig")
     	 */
    	public function indexAction()
    	{
			$products = $this->container->get('rm')->getRepository('mycompany:Products')->findAll();
			...
			return array("products" => $products);
		}
	}

Alternatively, you can refine the set of data you want to get using the `findBy()` method:

    	/**
     	 * @Route("/category/{category}", name="person.index")
     	 * @Template("ProductsBundle:index.html.twig")
     	 */
    	public function categoryAction($category)
    	{
			$products = $this->container->get('rm')
				->getRepository('mycompany:Products')
				->findBy(array('mycompany:category' => $category));
			...
			return array("products" => $products);
		}

You can then display your data using twig:

	<ul>
    	{% for product in products %}
        	<li class="list-group-item">{{ product['rdfs:label'].value }}</li>
      	{% endfor %}
    </ul>

If you need to encapsulate specific logic over your data, you can overload the default resource abstraction class. Overloading class must be defined in a RdfResource directory of your bundle directory:


	+-- ProductBundle
	|   +-- Controller
	|   +-- Entity
	|   +-- Dependency
	|   +-- Resource
	|   +-- RdfResource
	|		+-- Product

and must extend `Conjecto\Nemrod\Resource`:
	
	<?php
	
	namespace ConjectoDemo\EasyRdfBundle\RdfResource;
	
	use Conjecto\Nemrod\Resource as BaseResource;
	use Conjecto\Nemrod\ResourceManager\Annotation\Resource;
	
	/**
	 * Class ExampleResource
	 * @package ConjectoDemo\EasyRdfBundle\RdfResource
	 * @Resource(types={"mycompany:Product"}, uriPattern = "mycompany:product:")
	 */
	class Genealogiste extends BaseResource
	{



Documentation
-------------
A more detailed documentation will be released soon.

Contributing
------------
