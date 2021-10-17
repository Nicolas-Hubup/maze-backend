<?php

namespace App\Controller;

use AdamBrett\ShellWrapper\Runners\Exec;
use App\Entity\Wallet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use AdamBrett\ShellWrapper\Command\Builder as CommandBuilder;
class UtxoController extends AbstractRestController
{
    /**
     * @return JsonResponse|Response
     * @Route("test/utxo", methods={"GET"})
     */
    public function testUtxo()
    {
        /* This is going to be some dayum long but hey. Gotta do it */
        $output = [];
        /* Step 1 : Defining variables */
        exec('echo $CARDANO_NODE_SOCKET_PATH', $output);
        exec('cd /home/ubuntu/cardano-src/nft && cat payment.addr', $paymentAddr);
        exec('export CARDANO_NODE_SOCKET_PATH="/home/ubuntu/cardano-src/files/db/node.socket" && cd /home/ubuntu/cardano-src/nft && /home/ubuntu/.local/bin/cardano-cli query utxo --address ' . $paymentAddr[0] .  ' --mainnet 2>&1', $output);

        $repo = $this->getDoctrine()->getRepository(Wallet::class);
        $utxo = $repo->sqlFetch("SELECT DISTINCT(tx_hash) AS tx_hash FROM utxo");
        $knownTxHash = $repo->extractProperty('tx_hash', $utxo);
        unset($output[0]);
        unset($output[1]);
        unset($output[2]);
        for($x = 3; $x < sizeof($output); $x++) {
            var_dump($output[$x]);
        }
        $arrayWithoutKeys = array_values($output);
        $params = [];
        foreach($arrayWithoutKeys as $_transaction) {
            $exploded = explode(' ', $_transaction);
            if(!in_array($exploded[0], $knownTxHash)) {
                $params[] = $exploded[0];
                $params[] = $exploded[5];
            }
        }
        if(!empty($params)) {
            $repo->sqlExec("INSERT INTO utxo (tx_hash, tx_ix) VALUES " . $repo->genNupletsQMS(sizeof($params) / 2, 2), $params);
        }


        exec('export CARDANO_NODE_SOCKET_PATH="/home/ubuntu/cardano-src/files/db/node.socket" && cd /home/ubuntu/cardano-src/nft && /home/ubuntu/.local/bin/cardano-cli query protocol-parameters --mainnet --out-file protocol2.json 2>&1', $output);
        return $this->success($output);
    }

    /**
     * @Route("try/shell/wrapper", methods={"GET"})
     */
    public function tryShellWrapper()
    {
        $shell = new Exec();
        $command = new CommandBuilder('cardano-cli');
        $command->addSubCommand('query tip');
        $command->addArgument('mainnet');
        $output = $shell->run($command);

        return $this->success($output);
    }

    /**
     * @return JsonResponse|Response
     * @Route("hello", methods={"GET"})
     */
    public function exec()
    {
        $output = [];
        exec('groups', $output);
        return $this->success($output);
    }
}
