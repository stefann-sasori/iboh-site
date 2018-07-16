<?php

namespace Dup\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Dup\UserBundle\Service\RoleManager;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
    /* A user is considered online if the current time minus his lastActionDate is less than the MIN_UNACTIVE_PERIOD;
     * MIN_UNACTIVE_PERIOD = 10  => user must have been active  less than 10 seconds ago for being considered online
     */
    const MIN_UNACTIVE_PERIOD = 30;
    /**
     * @param string $role
     *
     * @return array
     */
    public function findByRole($role)
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.roles LIKE :roles')
            ->setParameter('roles', '%"'.$role.'"%');
        return $qb->getQuery()->getResult();
    }

    private function findUsers(Privilege $privilege = null, $page = 1, $byPage = 10, $online = true){
        $interval = new \DateInterval('PT'.self::MIN_UNACTIVE_PERIOD.'S');
        $now = new \DateTime();
        $date = $now->sub($interval);
        $qb = $this->createQueryBuilder('u');
        $comparator = $online ? '>' : '<=';
        $qb->where('u.lastActionDate '.$comparator.' :date')
            ->setParameter('date', $date);
        $users = [];
        if(!is_null($privilege)){
            $result = $qb->getQuery()
                   ->getResult();
            if(!empty($result)){
                $result = new ArrayCollection($result);
                $manager = new RoleManager();
                $users = $manager->filterByPrivilege($privilege, $result)->toArray();
            }
        }else{
            $users = $qb->getQuery()->getArrayResult();
        }
        return $this->paginate($page, $byPage, $users);
    }

    public  function findOfflineUsers(Privilege $privilege = null, $page = 1, $byPage = 10){
        return $this->findUsers($privilege, $page, $byPage, false);
    }
    public function findOnlineUsers(Privilege $privilege = null, $page = 1, $byPage = 10){
        return $this->findUsers($privilege, $page, $byPage);
    }

    public function findMany($list){
        $qb = $this->createQueryBuilder('a');
        $qb->add('where', $qb->expr()->in('a.id', ':list'))
            ->setParameter('list', $list);

        return $qb->getQuery()->getResult();
    }
    private function paginate($page, $byPage, array $items = []){
        $firstRes = ($page - 1) * $byPage;
        $lastRes = $firstRes + $byPage;
        $paginatedResult = [];
        for($i = $firstRes; $i < $lastRes; $i++){
            if(array_key_exists($i, $items)){
                $paginatedResult[] = $items[$i];
            }else{
                break;
            }
        }
        return $paginatedResult;
    }
}
