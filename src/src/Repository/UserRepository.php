<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
	private $passwordEncoder;

	public function __construct(ManagerRegistry $registry, UserPasswordEncoderInterface $passwordEncoder)
	{
		$this->passwordEncoder = $passwordEncoder;
		parent::__construct($registry, User::class);
	}

	/**
	 * Used to upgrade (rehash) the user's password automatically over time.
	 * Used by symfony
	 */
	public function upgradePassword(UserInterface $user, string $newEncodedPassword) : void
	{
		if (!$user instanceof User) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
		}
		$user->setPassword($newEncodedPassword);
		$this->_em->persist($user);
		$this->_em->flush();
	}

	public function insert(string $username, string $password, string $email, string $twitter_name)
	{
		$user = new User();
		$user->setUsername($username);
		$user->setEmail($email);
		$user->setPassword($this->passwordEncoder->encodePassword($user, $password));
		$user->setTwitterName($twitter_name);
		$user->setRoles($user->getRoles());
		$this->_em->persist($user);
		$this->_em->flush();
		return $user;
	}

	/**
	 * @param User $user
	 */
	public function delete(User $user)
	{
		$this->_em->remove($user);
		$this->_em->flush();
	}

	/**
	 * @param User $user
	 * @param array $data
	 */
	public function update(User $user, array $data)
	{
		if (!empty($data['username'])) {
			$user->setUsername($data['username']);
		}
		if (!empty($data['email'])) {
			$user->setEmail($data['email']);
		}
		if (!empty($data['password'])) {
			$user->setPassword($this->passwordEncoder->encodePassword($user, $data['password']));
		}
		if (!empty($data['twitter_name'])) {
			$user->setTwitterName($data['twitter_name']);
		}
		$this->_em->persist($user);
		$this->_em->flush();
		return $user;
	}

	/**
	 * @param User $user
	 * @param array $data
	 */
	public function checkPassword(User $user, string $password)
	{
		return $this->passwordEncoder->isPasswordValid($user, $password);
	}
}
