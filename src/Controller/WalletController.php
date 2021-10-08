<?php

namespace App\Controller;

use App\Entity\Utxo;
use App\Entity\Wallet;
use App\Entity\WalletAddress;
use App\Tools\Date\DateTools;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WalletController extends AbstractRestController
{

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("wallet/{wallet}/get/state", methods={"GET"})
     */
    public function getStateOfWallet(Wallet $wallet)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        if(curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        }
        curl_close($curl);
        $data = json_decode($result, true);
        return $this->success($data);
    }
    /**
     * @Route("generate/cardano/wallet", methods={"GET"})
     */
    public function generateWallet()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://localhost:1337/v2/wallets',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{"name": "Yan\'s wallet", "mnemonic_sentence": ["time", "skull", "brown", "shrug", "room", "goddess", "usage", "guilt", "index", "vacuum", "response", "voice", "exhaust", "basic", "blame", "random", "trigger", "together", "prison", "benefit", "leader", "fever", "side", "mad"], "passphrase": "Pisiform333"}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("create/address/for/given/wallet/{wallet}", methods={"GET"})
     */
    public function createAddressForGivenWallet(Wallet $wallet)
    {
        $em = $this->getDoctrine()->getManager();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId() . '/addresses?state=unused');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        if(curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }

        curl_close($ch);
        $payload = json_decode($result, true);
        $data = [];
        foreach($payload as $unusedAddress) {
            if($this->getDoctrine()->getRepository(WalletAddress::class)->findOneBy(["walletAddressId" => $unusedAddress["id"]]) === null) {
                $address = new WalletAddress();
                $address
                    ->setWallet($wallet)
                    ->setWalletAddressId($unusedAddress["id"])
                    ->setState("unused");
                $em->persist($address);
                $data[] = $address;
            }
        }


        $em->flush();

        return $this->success($data, "wallet_address", 200);
    }

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("wallet/{wallet}/update/balance", methods={"GET"})
     */
    public function updateBalanceOfGivenWallet(Wallet $wallet)
    {
        $em = $this->getDoctrine()->getManager();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        if(curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        }
        $payload = json_decode($result, true);
        var_dump($payload);
        $wallet
            ->setLovelaceBalance($payload["balance"]["total"]["quantity"])
            ->setLastUpdated(DateTools::getNow());
        if((int)$payload["balance"]["total"]["quantity"] !== 0) {
            $adaBalance = (int)$payload["balance"]["total"]["quantity"] / 1000000;
            $wallet->setAdaBalance($adaBalance);
        } else {
            $wallet->setAdaBalance(0);
        }
        $em->persist($wallet);
        $em->flush();
        curl_close($curl);
        return $this->success($wallet, "wallet", 200);
    }

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("wallet/{wallet}/update/utxos", methods={"GET"})
     */
    public function updateUtxosOfGivenWallet(Wallet $wallet)
    {
        $em = $this->getDoctrine()->getManager();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId() . '/utxo');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);


        $result = curl_exec($curl);
        if(curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        }
        $payload = json_decode($result, true);
        $wallet->setLastUpdatedUtxos(DateTools::getNow());
        $em->persist($wallet);
        if(!empty($payload["entries"])) {
            foreach($payload["entries"] as $_utxo) {
                $utxo = new Utxo();
                $utxo
                    ->setWallet($wallet)
                    ->setAdaBalance((int)$_utxo["ada"]["quantity"] / 1000000);
                $em->persist($utxo);
            }
        }
        $em->flush();
        return $this->success($payload, "wallet", 200);
    }

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("wallet/{wallet}/get/unused/address", methods={"GET"})
     */
    public function useAddressOfGivenWallet(Wallet $wallet)
    {
        $em = $this->getDoctrine()->getManager();
        $walletAddress = $this->getDoctrine()->getRepository(WalletAddress::class)->findOneBy(["wallet" => $wallet->getId(), "state" => "unused"]);
        $addressToUse = $walletAddress->getWalletAddressId();
        $walletAddress
            ->setState("used");
        $em->persist($walletAddress);
        $em->flush();
        return $this->success($addressToUse);
    }
}
