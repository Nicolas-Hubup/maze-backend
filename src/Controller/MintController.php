<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Entity\WalletAddress;
use App\Tools\Date\DateTools;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MintController extends AbstractRestController
{
    /**
     * @return JsonResponse|Response
     * @Route("fetch/available/price", methods={"GET"})
     */
    public function fetchAvailablePrice()
    {
//        $em = $this->getDoctrine()->getManager();
        $figure = $this->getDoctrine()->getRepository(Figure::class)->findOneBy(["available" => 1]);
        $decimal = $figure->getRandomNumber();
        $adaPrice = "35," . strval($decimal);

//        $figure->setAvailable(false);
//        $figure->setReservedAt(DateTools::getNow());

        $walletAddress = $this->getDoctrine()->getRepository(WalletAddress::class)->findOneBy(["state" => "unused"]);
        $address = $walletAddress->getWalletAddressId();

//        $walletAddress->setState("used");
//        $em->persist($figure);
//        $em->persist($walletAddress);
//        $em->flush();
        $data = [];
        $data["ada"] = $adaPrice;
        $data["address"] = $address;

        return $this->success($data);
    }
}
