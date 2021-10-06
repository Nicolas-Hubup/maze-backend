<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractRestController
{
    /**
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @return JsonResponse|Response
     * @Route("/api/m/user/create/admin", methods={"POST"})
     */
    public function createAdminUser(Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $em = $this->getDoctrine()->getManager();
        $payload = json_decode($request->getContent(), true);
        if($this->getDoctrine()->getRepository(User::class)->findOneBy(["email" => $payload["email"]]) !== null) {
            return $this->cex("An user already uses this email.");
        }
        $user = new User();
        $user
            ->setEmail($payload["email"])
            ->setPassword($passwordHasher->hashPassword($user, $payload["password"]))
            ->setRoles(["GOD"]);
        $em->persist($user);
        $em->flush();

        return $this->success($user, "user", 200);

    }
}
