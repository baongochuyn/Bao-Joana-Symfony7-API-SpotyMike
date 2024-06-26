<?php

namespace App\Repository;

use App\Entity\Album;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Album>
 *
 * @method Album|null find($id, $lockMode = null, $lockVersion = null)
 * @method Album|null findOneBy(array $criteria, array $orderBy = null)
 * @method Album[]    findAll()
 * @method Album[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AlbumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Album::class);
    }

       /**
        * @return Album[] Returns an array of Album objects
        */
       public function findById($id): array
       {
            return $this->createQueryBuilder('al')
                ->select('al', 'a', 'u', 's')
                ->leftJoin('al.artist_User_idUser', 'a')
                ->leftJoin('a.User_idUser', 'u')
                ->leftJoin('al.song_idSong', 's')
                ->where('al.active = 1')
                ->andWhere('al.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getResult();
       }

       /**
        * @return Album[] Returns an array of Album objects
        */
        public function findAlbums(): array
        {
             return $this->createQueryBuilder('al')
                 ->select('al', 'a', 'u', 's')
                 ->leftJoin('al.artist_User_idUser', 'a')
                 ->leftJoin('a.User_idUser', 'u')
                 ->leftJoin('al.song_idSong', 's')
                 ->where('al.active = 1')
                 ->getQuery()
                 ->getResult();
        }
    //    public function findOneBySomeField($value): ?Album
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
