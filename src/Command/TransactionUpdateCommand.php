<?php

namespace App\Command;

use App\Entity\Transaction;
use App\Entity\Wallet;
use App\Entity\WalletAddress;
use App\Tools\Date\DateTools;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TransactionUpdateCommand extends Command
{

    protected static $defaultName = 'transaction:update';

    private $em;

    public function __construct(EntityManagerInterface $em, $name = null)
    {
        parent::__construct($name);
        $this->em = $em;
    }


    protected function configure()
    {
        $this
            ->setDescription('Update every 15 seconds transaction database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $wallet = $this->em->getRepository(Wallet::class)->findOneBy(["id" => 1]);
        $repo = $this->em->getRepository(Transaction::class);
        $SQLTransactionInsert = " INSERT INTO transaction (wallet_id, transaction_id, direction, created_at, sender_output_address, lovelace_amount, ada_amount, is_valid, refund_proceeded) VALUES ";
        $params = [];
        while(true) {
            /* Instantiate curl */
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'http://localhost:1337/v2/wallets/' . $wallet->getWalletId() . '/transactions');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);
            if(curl_errno($curl)) {
                echo 'Error:' . curl_error($curl);
            }
            /* Fetching known transactions */
            $addresses = $this->em->getRepository(WalletAddress::class)->returnArrayOfAddressesForGivenWallet($wallet->getId());
            $transactions = $repo->sqlFetch("SELECT DISTINCT(transaction_id) AS transaction_id FROM transaction");
            $knownTransactions = $repo->extractProperty('transaction_id', $transactions);

            $walletAddresses = $repo->sqlFetch("SELECT DISTINCT(wallet_address_id) AS wallet_address_id FROM wallet_address");
            $knownAddresses = $repo->extractProperty('wallet_address_id', $walletAddresses);

            /* Fetching valid amount for transactions */
            $figureValues = $repo->sqlFetch("SELECT DISTINCT(random_number) AS random_number FROM figure");
            $validTransactionsAmount = $repo->extractProperty('random_number', $figureValues);

            $payload = json_decode($result, true);
            $newTransaction = 0;
            $validTransaction = 0;
            $flaggedTransaction = 0;
            foreach($payload as $_transaction) {
                if($_transaction["direction"] === "incoming" && !in_array($_transaction["id"], $knownTransactions)) {
                    ++$newTransaction;
                    /* In that case, we know that this transaction is a new one but we don't know yet if this transaction is valid */

                    foreach($_transaction["outputs"] as $_subOutput) {
                        if(!in_array($_subOutput["address"], $knownAddresses)) {
                            $senderOutPutAddress = $_subOutput["address"];
                        }
                    }

                    $adaAmount = $_transaction["amount"]["quantity"] / 1000000;
                    $params[] = $wallet->getId();
                    $params[] = $_transaction["id"];
                    $params[] = $_transaction["direction"];
                    $params[] = DateTools::toSQLString(DateTools::parseIsoAtomString($_transaction["inserted_at"]["time"]));
                    $params[] = $senderOutPutAddress;
                    $params[] = $_transaction["amount"]["quantity"];
                    $params[] = $adaAmount;

                    $explodedAdaAmount = explode('.', strval($adaAmount));
                    $integer = $explodedAdaAmount[0];
                    $decimal = $explodedAdaAmount[1];
                    $expectedDecimalLenght = 6;
                    if(strlen($decimal) < $expectedDecimalLenght) {
                        $missingZeros = $expectedDecimalLenght - strlen($decimal);
                        $decimal .= str_repeat("0", $missingZeros);
                    }
                    if(in_array($decimal, $validTransactionsAmount) && $integer === "1") {
                        $params[] = 1;
                        ++$validTransaction;
                        /* ¯\_(ツ)_/¯ Logic need to be here. Good luck Yan. ¯\_(ツ)_/¯ */
                    } else {
                        $params[] = 0;
                        ++$flaggedTransaction;
                    }
                    $params[] = 0;
                }
            }
            if(!empty($params)) {
                $repo->sqlExec($SQLTransactionInsert . $repo->genNupletsQMS(sizeof($params) / 9, 9), $params);
            }
            $params = [];
            $content = "Time is currently : " . DateTools::toSQLString(DateTools::getNow()) . " | " .
                $newTransaction . " new transaction(s) detected ( ". $validTransaction . " valid transactions | " . $flaggedTransaction . " flagged transactions ). Now sleeping for 2 seconds.";
            if($newTransaction > 0) {
                $io->success($content);
            } else {
                $io->info($content);
            }
            sleep(2);
        }
        return 0;
    }
}