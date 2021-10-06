<?php

namespace App\Controller;

use App\Entity\Utxo;
use App\Entity\Wallet;
use App\Entity\WalletAddress;
use App\Tools\Date\DateTools;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
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

    /**
     * @Route("start/cardano/node", methods={"GET"})
     */
    public function startCardanoNode()
    {
        $cmdLine = "cardano-node run --topology files/mainnet-topology.json --database-path files/db/ --socket-path files/db/node.socket --host-addr 172.31.35.249 --port 1337 --config files/mainnet-config.json";
        shell_exec("cd ~/cardano-src;" . $cmdLine . " > /dev/null &");
        return $this->success("Cardano node started");
    }

    /**
     * @Route("another/test", methods={"GET"})
     */
    public function anotherTest()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost:1337/v2/network/information');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        return $this->success(json_decode($result));
    }

    /**
     * @return JsonResponse|Response
     * @Route("generate/recovery/phrase", methods={"GET"})
     */
    public function generateRecoveryPassPhrase()
    {
        $process = new Process(['/home/ubuntu/.local/bin/cardano-wallet recovery-phrase generate'], '/home/ubuntu/cardano-src');
//        $process = Process::fromShellCommandline("cardano-wallet recovery-phrase generate", '/home/ubuntu/cardano-src');
        $process->run();
        if(!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $return = $process->getOutput();
        return $this->success($return);
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
