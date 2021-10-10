<?php

namespace App\Controller;

use App\Entity\Wallet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionController extends AbstractRestController
{
    /**
     * @param Request $request
     * @return JsonResponse|Response
     * @Route("transaction/create", methods={"POST"})
     */
    public function sendAda(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        $adaAmount = $payload["adaAmount"];
        $addressToSent = $payload["addressToSent"];
        $wallet = $this->getDoctrine()->getRepository(Wallet::class)->findOneBy(["walletId" => $payload["walletId"]]);

        if($adaAmount > $wallet->getAdaBalance()) {
            return $this->cex("Not enough funds in this wallet to respect this transaction.");
        }

        $url = "localhost:1337/v2/wallets/" . $wallet->getWalletId() . "/transactions";
//        $curlPayload = ["passphrase" => $wallet->getPassPhrase(), "payments" => [
//           "address" => $addressToSent,
//           "payments" => [
//               "amount" => [
//                   "quantity" => $adaAmount * 1000000,
//                   "unit" => "lovelace"
//               ]
//           ]
//        ]];
        $curlPayload = '{"passphrase": "' . $wallet->getPassPhrase() .'", "payments":[{"address":"'.$addressToSent . '", "amount": {"quantity":'. $adaAmount * 1000000 .', "unit":"lovelace"}}]}';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $curlPayload,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $this->success($response);
    }
}
