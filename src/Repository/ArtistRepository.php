<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 *
 * @method Artist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Artist|null findOneBy(array $criteria, array $orderBy = null)
 * @method Artist[]    findAll()
 * @method Artist[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

       /**
        * @return Artist[] Returns an array of Artist objects
        */
       public function findArtists(): array
       {
           return $this->createQueryBuilder('a')
                ->select('a', 'u', 'al', 's')
                ->leftJoin('a.User_idUser', 'u')
                ->leftJoin('a.albums', 'al')
                ->leftJoin('a.songs', 's')
                ->where('a.active = 1')
                ->getQuery()
                ->getResult()
           ;
       }

       public function findOneByFullname($value): ?Artist
       {
           return $this->createQueryBuilder('a')
                ->select('a', 'u', 'al', 's')
                ->leftJoin('a.User_idUser', 'u')
                ->leftJoin('a.albums', 'al')
                ->leftJoin('a.songs', 's')
                ->where('a.active = 1')
                ->andWhere('a.fullname = :fullname')
                ->setParameter('fullname', $value)
                ->getQuery()
                ->getOneOrNullResult()
           ;
       }
}
