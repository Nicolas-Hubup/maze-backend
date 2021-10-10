<?php

namespace App\Repository;

use App\Entity\WalletAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WalletAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method WalletAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method WalletAddress[]    findAll()
 * @method WalletAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WalletAddressRepository extends HelperRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletAddress::class);
    }

    public function returnArrayOfAddressesForGivenWallet($walletId)
    {
        $addresses = $this->sqlFetch("SELECT wallet_address_id FROM wallet_address WHERE wallet_id = ?", $walletId);
        return $this->extractProperty('wallet_address_id', $addresses);
    }


}
