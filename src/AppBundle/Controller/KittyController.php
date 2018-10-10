<?php
/**
 * User: quentinvdk
 * Date: 05/10/18
 * Time: 10:01
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Kitty;
use AppBundle\Form\KittyType;
use AppBundle\Repository\KittyRepository;
use Application\Sonata\MediaBundle\Entity\Media;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class KittyController extends Controller
{
    protected $kittyRepository;
    protected $entityManager;
    protected $securityChecker;

    public function __construct(KittyRepository $kittyRepository, EntityManagerInterface $entityManager, Security $securityChecker)
    {
        $this->kittyRepository = $kittyRepository;
        $this->entityManager = $entityManager;
        $this->securityChecker = $securityChecker;
    }


    /**
     * é@param Request $request
     * @Rest\View()
     * @Rest\Get("/api/kitties")
     * @Rest\QueryParam(name="page", nullable=true, default=1,requirements="\d+" )
     * @Rest\QueryParam(name="perPage", nullable=true, default="2", requirements="\d+")
     * @ApiDoc(description="Find all kitties", resource=true)
     * @return array
     */
    public function getKittiesAction(ParamFetcher $paramFetcher)
    {
        $maxResult = $paramFetcher->get('perPage');
        $kiityNumber = $this->kittyRepository->countAllKitties();
        $numberOfPage = ceil($kiityNumber/$maxResult);
        if ($this->securityChecker->getToken() && $this->securityChecker->isGranted('ROLE_USER')) {
            $kitties = $this->kittyRepository->findKittiesByPage($paramFetcher->get('page'), $maxResult);
        } else {
            $kitties = $this->kittyRepository->findKittiesNameByPage($paramFetcher->get('page'), $maxResult);
        }

        $data = array('data' => $kitties, 'meta' => array('pageNumber' =>$numberOfPage, 'perPage' =>$maxResult));//Test
        return $data;
    }
    /**
     * @param Request $request
     * @Rest\View()
     * @Rest\Get("/api/kitties/{id}")
     * @return Object |JsonResponse
     * @ApiDoc(description="Obtenir les information d'un chaton en particulier", requirements={
     *  {
     *     "name"="id",
     *     "dataType"="integer",
     *     "requirement"="\d+",
     *     "description"="L'id du chaton à obtenir."
     *   }
     *     })
     */
    public function getKittyAction(Request $request)
    {
        $kitty =  $this->kittyRepository->find($request->get('id'));
        if (empty($kitty)) {
            return new JsonResponse(array('message' => 'Kitty not found'), Response::HTTP_NOT_FOUND);
        }
        return $kitty;
    }

    /**
     * * @Rest\Get("/api/kitties/")
     */
    public function redirectToGetKittiesAction()
    {
        return $this->redirectToRoute('get_kitties');
    }

    /**
     * @param Request $request
     * @Rest\View()
     * @Rest\Delete("/api/kitties/{id}")
     * @ApiDoc(description="Supprime un chaton en particulier", requirements={
     *  {
     *     "name"="id",
     *     "dataType"="integer",
     *     "requirement"="\d+",
     *     "description"="Le chaton à effacer"
     *   }
     *     })
     * @return JsonResponse
     */
    public function deleteKittyAction(Request $request)
    {
        $kitty = $this->kittyRepository->find($request->get('id'));
        if (empty($kitty)) {
            return new JsonResponse(array('message' => 'Kitty not found'), Response::HTTP_NOT_FOUND);
        }
        $this->entityManager->remove($kitty);
        $this->entityManager->flush();
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @Rest\View(statusCode=Response::HTTP_CREATED)
     * @Rest\Post("/api/kitties")
     * @\Sensio\Bundle\FrameworkExtraBundle\Configuration\Security("has_role('ROLE_ADMIN')")
     */
    public function createKittyAction(Request $request)
    {
        $newKitty = new Kitty();
        $kittyForm = $this->createForm(KittyType::class, $newKitty);
        $kittyForm->submit($request->request->all()); // Validation des données
        if ($kittyForm->isValid()) {
            $fileInfo = $request->files->get('image');
            return $this->kittyRepository->createKitty($newKitty, $fileInfo);
        } else {
            return $kittyForm;
        }
    }

    /**
     * @param Request $request
     * @Rest\View(statusCode=Response::HTTP_OK)
     * @Rest\Put("/api/kitties/{id}")
     */
    public function updateKittyAction(Request $request)
    {
        $kitty = $this->kittyRepository->find($request->get('id'));
        if (empty($kitty)) {
            return new JsonResponse(array('message' => 'Kitty not found'), Response::HTTP_NOT_FOUND);
        } else {
            $kittyForm = $this->createForm(KittyType::class, $kitty);
            $kittyForm->submit($request->request->all(), false); // Validation des données
            if ($kittyForm->isValid()) {
                $this->entityManager->flush();
                $kitty = $this->kittyRepository->find($request->get('id'));
                return $kitty;
            } else {
                return $kittyForm;
            }
        }
    }
}
