Nemrod
======

What is Nemrod?
-----------------
Nemrod is a framework providing an abstraction layer for handling (consuming and producing) RDF in a Symfony2 project,
in the same way Symfony users are using Doctrine. The framework provides five main components:

- a resource manager (similar to Doctrine's resource manager)
- a SPARQL query builder allowing to interact directly with your sparql endpoint(s)
- a form extension allowing to build forms to create or update data
- a json-ld serializer allowing to produce framed json-ld representation of RDF
- Optionally, a set of services can be set up that populates and update an Elasticsearch server wrt triple store content.

Nemrod mainly relies on

- [EasyRdf](https://github.com/njh/easyrdf)
- [Json-ld](https://github.com/lanthaler/JsonLD)
- [Elastica](https://github.com/ruflin/Elastica)
- [jms/serializer](https://github.com/schmittjoh/serializer)



Demo project
------------
A quick way to test Nemrod Abilities before installing it into your project is to try our [demo project](https://github.com/conjecto/nemrod-demo)

Requirements
------------
- Symfony > 2.5, [Begin a Symfony Project](https://symfony.com/download)

Installation
------------
Nemrod can be installed using composer :

    composer require conjecto/nemrod:master easyrdf/easyrdf:@dev conjecto/json-ld:@dev

you can also add dependency directly in your composer.json file:

    "conjecto/nemrod": "master"
    "easyrdf/easyrdf": "@dev"
    "conjecto/json-ld": "@dev"

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
        #add the namespaces you need
		mycompany: "http://www.example.org/mycompany"

At this point, Nemrod knows enough to access to your data. Use the 'rm' service's `findAll()` (which is an alias for 'nemrod.resource\_manager.my\_endpoint'):

	<?php
	
	use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
	use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

	class ProductsController extends Controller
	{
    	/**
     	 * @Route("/all/", name="product.index")
     	 * @Template("ProductsBundle:index.html.twig")
     	 */
    	public function indexAction()
    	{
			$products = $this->container->get('rm')->getRepository('mycompany:Product')->findAll();
			...
			return array("products" => $products);
		}
	}

Alternatively, you can refine the set of data you want to get using the `findBy()` method:

    	/**
     	 * @Route("/category/{category}", name="product.category")
     	 * @Template("ProductsBundle:index.html.twig")
     	 */
    	public function categoryAction($category)
    	{
			$products = $this->container->get('rm')
				->getRepository('mycompany:Product')
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

Another possibility is to ask for a specific resource using its uri:

    	/**
     	 * @Route("/view/{uri}", name="product.view")
     	 * @Template("ProductsBundle:view.html.twig")
     	 */
    	public function viewAction($uri)
    	{
			$product = $this->container->get('rm')
				->getRepository('mycompany:Product')
				->find($uri);
			...
			return array("product" => $product);
		}

You can also use the paramConverter to get the resource directly. In this example, the product variable is automatically instanciated.

        /**
     	 * @Route("/view/{uri}", name="product.view")
     	 * @ParamConverter("product", class="mycompany:Product")
     	 * @Template("ProductsBundle:view.html.twig")
     	 */
    	public function viewAction($product)
    	{
			return array("product" => $product);
		}
		
If you need to encapsulate specific logic over your data, you can overload the default resource abstraction class. Overloading class must be defined in a RdfResource directory of your bundle directory:


	+-- ProductBundle
	|   +-- Controller
	|   +-- DependencyInjection
	|   +-- Resources
	|   +-- RdfResource
	|		+-- Product

and must extend `Conjecto\Nemrod\Resource` (which is the default abstraction class):
	
	<?php
	
	namespace MyCompany\ProductBundle\RdfResource;
	
	use Conjecto\Nemrod\Resource as BaseResource;
	use Conjecto\Nemrod\ResourceManager\Annotation\Resource;
	
	/**
	 * Class Product
	 * @Resource(types={"mycompany:Product"}, uriPattern = "mycompany:product:")
	 */
	class Product extends BaseResource
	{

	}

the @Resource annotation allows to map your class and RDF types, so you can get an instance of this class when asking for object with the given types:

	$product = $this->container->get('rm')->getRepository('mycompany:Product')->find($uri);

will fill `$products` with an array of `MyCompany\ProductBundle\RdfResource\Product` objects.

An URI pattern can also be provided with `uriPattern`. It will be used to set an URI for a new resource. For now, the specified pattern is just used as a prefix

Documentation
-------------

- [Using the Query Builder](doc/querybuilder.md)
- [Creating forms](doc/forms.md)
- [Serializing resources to Json-ld](doc/serializer.md)
- [Using ElasticSearch to index your data](doc/elasticsearch.md)

Contributing
------------

- Feel free to install and test the framework. We'd love to have some feedback about it.
- A lot of tests has to be written. We are currently putting effort on that point. Any help would be appreciated.


Licensing
-----
The Nemrod framework is licensed under the [BSD-3-Clause](LICENSE.md) license.
