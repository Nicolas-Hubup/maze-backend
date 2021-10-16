<?php

namespace App\Controller;

use App\Entity\Wallet;
use App\Tools\Date\DateTools;
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


    /**
     * @return JsonResponse|Response
     * @Route("/pre/test/refund", methods={"GET"})
     */
    public function preTestRefund()
    {
        $repo = $this->getDoctrine()->getRepository(Wallet::class);
        $wallet = $this->getDoctrine()->getRepository(Wallet::class)->findOneBy(["id" => 1]);
        $transactionsToRefund = $repo->sqlFetch("SELECT id, sender_output_address, ada_amount
                                                       FROM transaction
                                                       WHERE refund_proceeded = ?
                                                       AND direction = ?
                                                       AND ada_amount > ?
                                                       AND is_valid = ?", [0, "incoming", 0.19, 0]);

        $transactionIdsToRefund = $repo->extractProperty('id', $transactionsToRefund);

        $params = [];
        if(!empty($transactionsToRefund)) {
            $url = "localhost:1337/v2/wallets/" . $wallet->getWalletId() . "/transactions";
            $SQLGenerateRefund = "INSERT INTO refund (refunded_at, output_address, ada_amount, ada_amount_post_fees, transaction_id, curl_response) VALUES ";
            foreach($transactionsToRefund as $_transactionToRefund) {
                $adaAmountPostFees = $_transactionToRefund["ada_amount"] - 0.19;
                $params[] = DateTools::toSQLString(DateTools::getNow());
                $params[] = $_transactionToRefund["sender_output_address"];
                $params[] = $_transactionToRefund["ada_amount"];
                $params[] = $adaAmountPostFees;
                $params[] = $_transactionToRefund["id"];

                $curlPayload = '{"passphrase": "' . $wallet->getPassPhrase() .'", "payments":[{"address":"'. $_transactionToRefund["sender_output_address"] . '", "amount": {"quantity":'. $adaAmountPostFees * 1000000 .', "unit":"lovelace"}}]}';
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
                $params[] = $response;
                curl_close($curl);

            }
            $repo->sqlExec($SQLGenerateRefund . $repo->genNupletsQMS(sizeof($params) / 6, 6), $params);
            /* We now need to update our transaction database to not have to refund them anymore because we just did it */
            $SQLUpdateTransaction = "UPDATE transaction SET refund_proceeded = 1 WHERE id IN ";
            $repo->sqlExec($SQLUpdateTransaction . $repo->genParenthesesQMS(sizeof($transactionIdsToRefund)), $transactionIdsToRefund);
        }

        return $this->success($transactionsToRefund, null, 200, true);
    }
}
