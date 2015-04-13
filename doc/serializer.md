Serializing your data in Json-ld
=====

To serialize a resource to JsonLd, use the service nemrod.jsonld.serializer.
This service needs the resource to serialize and a JsonLd frame.

You can specify a frame with different ways.

You can use a file frame file like "@User/user-es.jsonld" :

    $jsonLd = $this->jsonLdSerializer->serialize(new Resource($uri), "@User/user-es.jsonld");

You can also specify a frame for a resource, a controller or an action.

Specify a frame for a resource like this:

    /**
     * Class User
     * @package Doc\UserBundle\RdfResource
     * @Resource(types={"foaf:Person"})
     * @frame ('{
     *     "@context": {
     *       "foaf": "http://xmlns.com/foaf/0.1/"
     *     },
     *     "@embed": "true",
     *     "@type": "foaf:Person",
     *     "foaf:name": {
     *       "@default": "",
     *       "@mapping": {
     *         "type": "string",
     *         "index": "not_analyzed"
     *       }
     *     }
     *   }')
     */
    class User extends BaseResource
    {
        ...
    }

Specify a frame for a controller like this:

    /**
     * @Route("/user")
     * @frame ('{
     *     "@context": {
     *       "foaf": "http://xmlns.com/foaf/0.1/"
     *     },
     *     "@embed": "true",
     *     "@type": "foaf:Person",
     *     "foaf:name": {
     *       "@default": "",
     *       "@mapping": {
     *         "type": "string",
     *         "index": "not_analyzed"
     *       }
     *     }
     *   }')
     **/
    class UserController extends Controller
    {
        ...
    }

Specify a frame for an action like this:

    /**
     * @Route("/user")
     **/
    class UserController extends Controller
    {
        /**
         * @Route("/serialize/{uri}", name="user.serialize", requirements={"uri" = ".+"})
         * @Template()
         * @ParamConverter("resource", class="foaf:Person")
         * @frame ('{
         *     "@context": {
         *       "foaf": "http://xmlns.com/foaf/0.1/"
         *     },
         *     "@embed": "true",
         *     "@type": "foaf:Person",
         *     "foaf:name": {
         *       "@default": "",
         *       "@mapping": {
         *         "type": "string",
         *         "index": "not_analyzed"
         *       }
         *     }
         *   }')
         */
        public function serializeAction(Request $request, User $resource)
        {
            return $this->jsonLdSerializer->serialize($resource);
        }
    }

The different ways to serialize a resource have different priorities. In order, if you specify a frame file path, it will have the highest priority.
After, the action frame has an highest priority that the controller frame. Finally, the controller frame has a highest priority that the resource frame.
