Serializing your data in Json-ld
=====

To serialize a resource to JsonLd, use the service nemrod.jsonld.serializer.
This service needs the resource to serialize and a JsonLd frame.

You can specify a frame with different ways.

You can use a file frame file like "@User/user.jsonld". 
With the specified value path '@User/user.jsonld', the file can be found at UserBundle/Resources/frames/user.jsonld.

    use Conjecto\Nemrod\Framing\Serializer\JsonLdSerializer;
    
    $jsonLd = $this->jsonLdSerializer->serialize($resource, "@User/user.jsonld");

You can also specify a frame for a resource, a controller or an action.

Specify a frame for a resource like this:

    use Conjecto\Nemrod\Framing\Annotation as Serializer;
    use Conjecto\Nemrod\ResourceManager\Annotation\Resource;
    use Conjecto\Nemrod\Resource as BaseResource;
    
    /**
     * Class User
     * @package Doc\UserBundle\RdfResource
     * @Resource(types={"foaf:Person"})
     * @Serializer\JsonLd(frame="@User/user.jsonld")
     */
    class User extends BaseResource
    {
        ...
    }

Specify a frame for a controller like this:

    /**
     * @Route("/user")
     * @Serializer\JsonLd(frame="@User/user.jsonld")
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
         * @Serializer\JsonLd(frame="@User/user.jsonld")
         */
        public function serializeAction(Request $request, User $resource)
        {
            return $this->jsonLdSerializer->serialize($resource);
        }
    }

The different ways to serialize a resource have different priorities. In order, if you specify a frame file path, it will have the highest priority.
After, the action frame has an highest priority that the controller frame. Finally, the controller frame has a highest priority that the resource frame.
