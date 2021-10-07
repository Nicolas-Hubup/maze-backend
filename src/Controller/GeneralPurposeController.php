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
        $process = new Process(['cardano-wallet recovery-phrase generate'], '/home/ubuntu/');
        $process->run();
        if(!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        $return = $process->getOutput();
        return $this->success($return);
    }

    /**
     * @return JsonResponse|Response
     * @Route("test", methods={"GET"})
     */
    public function returngetenv()
    {
        $last_line = system('/home/ubuntu/.local/bin/cardano-wallet recovery-phrase generate', $retval);
        return $this->success('Derniere ligne : ' . $last_line . ' valeur retournée : ' . $retval);
    }

    /**
     * @return JsonResponse|Response
     * @Route("test/v2", methods={"GET"})
     */
    public function secondTest()
    {
        $output = null;
        $output = shell_exec("export CARDANO_NODE_SOCKET_PATH='/home/ubuntu/cardano-src/files/db/node.socket'; /home/ubuntu/.local/bin/cardano-cli query tip --mainnet 2>&1");
        return $this->success(json_decode($output, true));
    }

    /**
     * @Route("run/cardano/node", methods={"GET"})
     */
    public function thirdTest()
    {
        $output = null;
        $output = shell_exec('/home/ubuntu/.local/bin/cardano-node run --topology files/mainnet-topology.json --database-path files/db/ --socket-path files/db/node.socket --host-addr 172.31.35.249 --port 1337 --config files/mainnet-config.json 2>&1');
        return $this->success("running");
    }


}
