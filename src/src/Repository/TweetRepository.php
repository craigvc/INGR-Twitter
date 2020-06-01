<?php

namespace App\Repository;

use App\Entity\Tweet;
use App\Entity\User;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use \DateTime;
use Twig\Node\Expression\Test\ConstantTest;

/**
 * @method Tweet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tweet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tweet[]    findAll()
 * @method Tweet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TweetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tweet::class);
    }

    public function insert(int $twitter_id, string $content, string $tweet_date, string $twitter_name, User $user)
    {
        $tweet = new Tweet();

        $tweet_date = DateTime::createFromFormat('D M j H:i:s P Y', $tweet_date);
        $tweet->setTwitterId((integer)$twitter_id);
        $tweet->setTweetContent($content);
        $tweet->setTwitterName($twitter_name);
        $tweet->setTweetDate($tweet_date);
        $tweet->setUser($user);
        $this->_em->persist($tweet);
        $this->_em->flush();
    }

    /**
     * @return Tweet[] Returns an array of Tweet objects for a specific user
     */
    public function findTweetsByUser(int $user_id)
    {
        return $this->createQueryBuilder('tweet')
            ->andWhere('tweet.user = :val')
            ->setParameter('val', $user_id)
            ->orderBy('tweet.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Tweet Returns a tweet object
     */
    public function findOneById($id): ?Tweet
    {
        return $this->createQueryBuilder('tweet')
            ->andWhere('tweet.twitter_id = :val')
            ->setMaxResults(1)
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(Tweet $tweet)
    {
        $this->_em->remove($tweet);
        $this->_em->flush();
    }
}