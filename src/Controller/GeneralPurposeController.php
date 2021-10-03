<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GeneralPurposeController extends AbstractRestController
{
    public function scriptGenerateRandomNumbers()
    {
        $sql = "INSERT INTO random_figures (random_figure) VALUES ";
        $unavailableValues = [];
        for ($i = 0; $i < 10000; $i++) {
            $sixFiguresNumber = random_int(100000, 999999);
            if(!in_array($sixFiguresNumber, $unavailableValues)) {
                $unavailableValues[] = $sixFiguresNumber;
            }
        }
    }
}
