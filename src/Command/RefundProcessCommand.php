<?php

namespace App\Command;


use App\Entity\Wallet;
use App\Tools\Date\DateTools;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RefundProcessCommand extends Command
{

    protected static $defaultName = 'refund:process';

    private $em;

    public function __construct(EntityManagerInterface $em, $name = null)
    {
        parent::__construct($name);
        $this->em = $em;
    }


    protected function configure()
    {
        $this
            ->setDescription('Process refunds');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->em->getRepository(Wallet::class);

        $wallet = $repo->findOneBy(["id" => 1]);

        while( true ) {
            $transactionsToRefund = $repo->sqlFetch("SELECT id, sender_output_address, ada_amount
                                                       FROM transaction
                                                       WHERE refund_proceeded = ?
                                                       AND direction = ?
                                                       AND ada_amount > ?
                                                       AND is_valid = ?", [0, "incoming", 1.20, 0]);

            $transactionIdsToRefund = $repo->extractProperty('id', $transactionsToRefund);
            $params = [];
            $refundProcessed = 0;
            if(!empty($transactionsToRefund)) {
                $url = "localhost:1337/v2/wallets/" . $wallet->getWalletId() . "/transactions";
                $SQLGenerateRefund = "INSERT INTO refund (refunded_at, output_address, ada_amount, ada_amount_post_fees, transaction_id, curl_response) VALUES ";
                foreach($transactionsToRefund as $_transactionToRefund) {
                    ++$refundProcessed;
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
            if($refundProcessed > 0) {
                $io->success($refundProcessed . " refund(s) proceed. Now sleep for 2 seconds");
            } else {
                $io->info("0 refund proceed. Now sleeping for 2 seconds");
            }
            sleep(2);
        }

        return 0;
    }
}