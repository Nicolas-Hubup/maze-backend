<?php


namespace App\Controller;


use App\Tools\Date\DateTools;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

abstract class AbstractRestController extends AbstractController
{
    protected static $default_strategy = "api_default_strategy";
    protected static $default_iri = "/default/iri";

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EntityManagerInterface $entityManager
     */
    protected $entityManager;

    /**
     * AbstractRestController constructor.
     * @param SerializerInterface $serializer
     * @param PublisherInterface $publisher
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(SerializerInterface $serializer, PublisherInterface $publisher, RequestStack $requestStack, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    /**
     * Returns true if the current user is in a supervisor unit
     */
    protected function userIsInSupervisorOperationalUnit()
    {
        $urlParameters = $this->requestStack->getMasterRequest()->query->all();
        if(!empty($urlParameters)) {
            if(key_exists("bearer", $urlParameters)) {
                $encoded_jwt = $urlParameters["bearer"];
                $payload = explode('.', $encoded_jwt)[1];
                $decoded_jwt = json_decode(base64_decode($payload), true);
            }
        } else {
            $encoded_jwt = $this->requestStack->getMasterRequest()->headers->get('Authorization');
            $payload = explode(".", explode(" ", $encoded_jwt)[1])[1];
            $decoded_jwt = json_decode(base64_decode($payload), true);
        }

        if(!array_key_exists("is_in_supervisor", $decoded_jwt)) {
            return false;
        } else {
            return $decoded_jwt["is_in_supervisor"];
        }
    }

    /**
     * @param null $content
     * @param null $strategy
     * @param null $iri
     */
    public function publishUpdate($content = null, $strategy = null, $iri = null)
    {

        try {
            set_time_limit(3);
            if($iri === null) {
                if(static::$default_iri === self::$default_iri) {
                    throw new \Exception("Any controller publishing update must implement 'protected static \$default_iri = xxx' or specify a custom iri in the given call. (controller " . get_class($this) . ")" );
                }
                $iri = static::$default_iri;
            }
            if($strategy === null) {
                if(static::$default_strategy === self::$default_strategy) {
                    throw new \Exception("Any controller publishing update must implement 'protected static \$default_strategy = xxx' or specify a custom strategy in the given call. (controller " . get_class($this) . ")");
                }
                $strategy = static::$default_strategy;
            }

            $request = $this->requestStack->getCurrentRequest();
            $topic = $request->getSchemeAndHttpHost() . "/api" . $iri;
            $update = new Update($topic , json_encode(['strategy' => $strategy, 'content' => $content, 'iri' => $topic, 'initiator' => parent::getUser()]));


            $this->logger->alert("[MERCURE] Emitting message : topic=$topic, content=" . json_encode(['strategy' => $strategy, 'content' => $content, 'iri' => $topic, 'initiator' => parent::getUser()]));
            $this->publisher->__invoke($update);
            return ["topic" => $topic, "content" => ['strategy' => $strategy, 'content' => $content, 'iri' => $topic, 'initiator' => parent::getUser()]];
        } catch(\Exception $exception) {
            $this->logger->alert("FAILED TO POST SOMETHING ON THE MERCURE.");
        }

    }

    /**
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     * @param $missionIds
     * @param $eventIds
     * @param bool $impactsVehicles
     * @param bool $impactsUsers
     */
    public function publishEventUpdate(\DateTimeInterface $start, \DateTimeInterface $end, $missionIds, $eventIds, bool $impactsVehicles, bool $impactsUsers): void
    {
        $this->publishUpdate([
            "startDay" => DateTools::toSQLString($start, true),
            "endDay" => DateTools::toSQLString($end, true),
            "missions" => is_array($missionIds) ? $missionIds : $missionIds === null ? [] : [$missionIds],
            "events" => is_array($eventIds) ? $eventIds : $eventIds === null ? [] : [$eventIds],
            "impact_users" => $impactsUsers,
            "impact_vehicles" => $impactsVehicles,
            "initiator" => $this->getUser()->getId()
        ]);
    }

    /**
     * $this->success($items(=null), "toto") // 204 no content
     * $this->success($items(=null), "toto", 200, true) // [] 200
     * @param $data
     * @param array $serializationGroups
     * @param int $code
     * @param false $emptyArrayIfVoid
     * @return JsonResponse|Response
     */
    protected function success($data = '', $serializationGroups = [], $code = Response::HTTP_OK, $emptyArrayIfVoid = false) {

        if(empty($data)) {
            if($emptyArrayIfVoid){
                return new JsonResponse([]);
            }
            return $this->void();
        }

        if(empty($serializationGroups)) {
            return new JsonResponse($data, $code);
        }

        return new Response($this->serialize($data, $serializationGroups), $code, ["content-type" => "application/json"]);

    }

    /**
     * @param $data
     * @param array $serializationGroups
     * @return string
     */
    public function serialize($data, $serializationGroups = []): string
    {
        $context = SerializationContext::create();
        $context->setSerializeNull(true);
        $context->setGroups($serializationGroups);

        return $this->serializer->serialize($data, 'json',$context);
    }

    //TODO return them all
    /**
     * @param $why
     * @return JsonResponse
     */
    public function cex($why): JsonResponse
    {
        return new JsonResponse(["catched_exception" => $why], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param $why
     * @return JsonResponse
     */
    public function notAcceptable(string $why): JsonResponse
    {
        return new JsonResponse(["catched_exception" => $why], Response::HTTP_NOT_ACCEPTABLE);
    }

    /**
     * @param string $why
     * @return JsonResponse
     */
    public function deny($why = "Access denied"): JsonResponse
    {
        return new JsonResponse(["catched_exception" => $why], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @return Response
     */
    public function void(): Response
    {
        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @return User
     */
    public function getUser() : User
    {
        $jwtUser = parent::getUser();
        return $this->entityManager->getRepository(User::class)->findOneByUsername($jwtUser->getUsername());
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return parent::getUser()->getUsername();
    }

    /**
     * @param $arr
     * @param $field
     * @return mixed
     * @throws RequestParsingException
     */
    public function getFieldOrError($arr, $field)
    {
        if(array_key_exists($field,$arr)) {
            return $arr[$field];
        }
        throw new RequestParsingException("Field '".$field."' is missing in array " . json_encode($arr));
    }

    /**
     * @param $arr
     * @param $field
     * @return mixed|null
     */
    public function getFieldOrNull($arr, $field)
    {
        return $arr[$field] ?? null;
    }
}