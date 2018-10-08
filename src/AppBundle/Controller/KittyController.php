<?php
/**
 * User: quentinvdk
 * Date: 05/10/18
 * Time: 10:01
 */

namespace AppBundle\Controller;

use AppBundle\Repository\KittyRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Request\ParamFetcher;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class KittyController extends Controller
{
    protected $kittyRepository;
    protected $entityManager;

    public function __construct(KittyRepository $kittyRepository, EntityManagerInterface $entityManager)
    {
        $this->kittyRepository = $kittyRepository;
        $this->entityManager = $entityManager;
    }


    /**
     * é@param Request $request
     * @Rest\View()
     * @Rest\Get("/api/kitties")
     * @Rest\QueryParam(name="page", nullable=true, default=1,requirements="\d+" )
     * @ApiDoc(description="Find all kitties", resource=true)
     * @return array
     */
    public function getKittiesAction(ParamFetcher $paramFetcher)
    {
        $kitties = $this->kittyRepository->findKittiesByPage($paramFetcher->get('page'));
        $data =array('data' => $kitties, 'meta' => array('pageNumber' =>99));//Test
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


}
