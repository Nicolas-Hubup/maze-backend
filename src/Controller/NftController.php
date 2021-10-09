<?php

namespace App\Controller;

use App\Entity\Figure;
use App\Entity\TemporaryJpeg;
use App\Tools\Date\DateTools;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NftController extends AbstractRestController
{
    //This controller has the purpose to import zip_files with potential jpegs or layers directories.
    //This controller has the purpose to write the algorithm of generating jpegs from layers directories with rarity table
    //This controller has the purpose to transform generated jpges to ipfs.
    //This controller has the purpose to stock ipfs and associate to random 6 figures integer.

    //That way, when someone will ask to mint a NFT : we'll fetch a 6 random figures for the payment.
    //Example : 35,384112 ADA
    //This associate ipfs will be fetch and the cardano-cli will be executed.
    //Easy right ? We'll see !


    /**
     * @Route("/api/import/zip/file/to/maze", methods={"POST"})
     * @throws Exception
     */
    public function importZipFileToServer(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $request->request->all();

        $_file = $request->files->all();
        $dirName = dirname(__DIR__, 2) ."/" . get_current_user() . "_cache";
        if (!is_dir($dirName)) {
            mkdir($dirName);
        }
        if (!is_writable($dirName)) {
            throw new Exception($dirName . " is not writtable. This is a major issue");
        }
        $zipFile = $_file["zip_file"];
        if(!is_dir($dirName . "/extracted")) {
            mkdir($dirName . "/extracted");
        }
        $fullPath = $dirName . "/extracted";
        $zip = new \ZipArchive();
        $res = $zip->open($zipFile);
        if($res) {
            $zip->extractTo($fullPath);
            $zip->close();
        }


        $scanned_directory = array_diff(scandir($fullPath), array('..', '.'));
        foreach($scanned_directory as $_file) {
            $file = new TemporaryJpeg();
            $file
                ->setFileName($_file)
                ->setUploadedAt(DateTools::getNow());
            $em->persist($file);
        }
        $em->flush();

        return $this->success("successfully imported");
    }


    /**
     * @return JsonResponse|Response
     * @throws Exception
     * @Route("generate/random/figures", methods={"GET"})
     */
    public function generateRandomFigures()
    {
        $em = $this->getDoctrine()->getManager();
        $data = [];
        $repo = $this->getDoctrine()->getRepository(Figure::class);
        $sqlInsert = "INSERT INTO figure (id, random_number) VALUES ";
        $tmpCount = 10000;
        for($x = 0; $x < $tmpCount; $x++) {
            $random_number = random_int(100000, 999999);
            if(!in_array($random_number, $data)) {
                $data[$random_number] = 0;
            }
            if(sizeof($data) === 10001) {
                break;
            } else {
                $tmpCount++;
            }
        }

        $finalData = [];
        foreach($data as $key => $value) {
            $finalData[] = $key;
            unset($data[$key]);
        }
        foreach($finalData as $_futureRandomFigure) {
            $figure = new Figure();
            $figure->setRandomNumber($_futureRandomFigure);
            $em->persist($figure);
        }
        $em->flush();

        return $this->success("done !");
    }

}
