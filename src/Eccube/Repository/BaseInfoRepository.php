<?php

namespace Eccube\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * BaseInfoRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BaseInfoRepository extends EntityRepository
{
    public function get($id = 1)
    {
        return $this->find($id);
    }
}
