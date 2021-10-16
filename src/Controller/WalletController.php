<?php

namespace App\Controller;

use App\Entity\TemporaryWalletAddress;
use App\Entity\Transaction;
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
        $em = $this->getDoctrine()->getManager();
        $payload = json_decode($response);
        $temporaryWallet = new TemporaryWalletAddress();
        $temporaryWallet
            ->setCreatedAt(DateTools::getNow());
        $em->persist($temporaryWallet);
        $em->flush();
        curl_close($curl);
        return $this->success($temporaryWallet, "temporary_wallet", 200);
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
        $wallet
            ->setLovelaceBalance($payload["balance"]["total"]["quantity"])
            ->setLastUpdated(DateTools::getNow());
        if((int)($payload["balance"]["total"]["quantity"]) !== 0) {
            $adaBalance = $payload["balance"]["total"]["quantity"] / 1000000;
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
                    ->setAdaBalance($_utxo["ada"]["quantity"] / 1000000);
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

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("wallet/{wallet}/get/all/addresses", methods={"GET"})
     */
    public function getAllAdressForGivenWallet(Wallet $wallet)
    {
        $repo = $this->getDoctrine()->getRepository(WalletAddress::class);
        $data = $repo->sqlFetch("SELECT wallet_address.id, wallet_address.wallet_address_id, wallet_address.state
                                       FROM wallet_address WHERE wallet_id = ?", $wallet->getId());
        return $this->success($data);
    }

    /**
     * @param Wallet $wallet
     * @return JsonResponse|Response
     * @Route("wallet/{wallet}/get/all/transactions", methods={"GET"})
     */
    public function getAllTransactionsForGivenWallet(Wallet $wallet)
    {
        $em = $this->getDoctrine()->getManager();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId() . '/transactions');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);
        if(curl_errno($curl)) {
            echo 'Error:' . curl_error($curl);
        }
        $repo = $this->getDoctrine()->getRepository(Transaction::class);
        $addresses = $this->getDoctrine()->getRepository(WalletAddress::class)->returnArrayOfAddressesForGivenWallet($wallet->getId());
        $transactions = $repo->sqlFetch("SELECT DISTINCT(transaction_id) AS transaction_id FROM transaction");
        $knownTransactions = $repo->extractProperty('transaction_id', $transactions);

        $walletAddresses = $repo->sqlFetch("SELECT DISTINCT(wallet_address_id) AS wallet_address_id FROM wallet_address");
        $knownAddresses = $repo->extractProperty('wallet_address_id', $walletAddresses);
        $payload = json_decode($result, true);
        foreach($payload as $_transaction) {
            if($_transaction["direction"] === "incoming" && !in_array($_transaction["id"], $knownTransactions)) {
                foreach($_transaction["outputs"] as $_subOutput) {
                    if(!in_array($_subOutput["address"], $knownAddresses)) {
                        $senderOutPutAddress = $_subOutput["address"];
                    }
                }
                $transaction = new Transaction();
                $transaction
                    ->setWallet($wallet)
                    ->setTransactionId($_transaction["id"])
                    ->setDirection($_transaction["direction"])
                    ->setCreatedAt(DateTools::parseIsoAtomString($_transaction["inserted_at"]["time"]))
                    ->setLovelaceAmount($_transaction["amount"]["quantity"])
                    ->setAdaAmount($_transaction["amount"]["quantity"] / 1000000)
                    ->setSenderOutputAddress($senderOutPutAddress);
                $em->persist($transaction);
            }
        }
        $em->flush();
        return $this->success($payload);
    }

    /**
     * @param Wallet $wallet
     * @Route("while/transaction", methods={"GET"})
     */
    public function whileTransaction(Wallet $wallet)
    {
        $em = $this->getDoctrine()->getManager();
        while(true) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId() . '/transactions');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            if(curl_errno($curl)) {
                echo 'Error:' . curl_error($curl);
            }
            $repo = $this->getDoctrine()->getRepository(Transaction::class);
            $addresses = $this->getDoctrine()->getRepository(WalletAddress::class)->returnArrayOfAddressesForGivenWallet($wallet->getId());
            $transactions = $repo->sqlFetch("SELECT DISTINCT(transaction_id) AS transaction_id FROM transaction");
            $knownTransactions = $repo->extractProperty('transaction_id', $transactions);

            $walletAddresses = $repo->sqlFetch("SELECT DISTINCT(wallet_address_id) AS wallet_address_id FROM wallet_address");
            $knownAddresses = $repo->extractProperty('wallet_address_id', $walletAddresses);
            $payload = json_decode($result, true);
            foreach($payload as $_transaction) {
                if($_transaction["direction"] === "incoming" && !in_array($_transaction["id"], $knownTransactions)) {
                    foreach($_transaction["outputs"] as $_subOutput) {
                        if(!in_array($_subOutput["address"], $knownAddresses)) {
                            $senderOutPutAddress = $_subOutput["address"];
                        }
                    }
                    $transaction = new Transaction();
                    $transaction
                        ->setWallet($wallet)
                        ->setTransactionId($_transaction["id"])
                        ->setDirection($_transaction["direction"])
                        ->setCreatedAt(DateTools::parseIsoAtomString($_transaction["inserted_at"]["time"]))
                        ->setLovelaceAmount($_transaction["amount"]["quantity"])
                        ->setAdaAmount($_transaction["amount"]["quantity"] / 1000000)
                        ->setSenderOutputAddress($senderOutPutAddress);
                    $em->persist($transaction);
                }
            }
            $em->flush();
            sleep(15);
        }
    }


}
