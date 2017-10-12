<?php

/**
 * HoldTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class HoldTable extends PluginHoldTable
{
  public function createQuery($alias = 'h', $light = false)
  {
    $culture = sfContext::hasInstance() ? sfContext::getInstance()->getUser()->getCulture() : 'fr';
    
    $q = parent::createQuery($alias)
      ->leftJoin("$alias.Translation ht WITH ht.lang = '$culture'");
    
    if ( !$light )
      $q->leftJoin("$alias.Manifestation m")
        ->select("$alias.*, ht.*, m.*")
        ->addSelect('(m.happens_at > NOW()) AS after')
        ->addSelect("(SELECT count(hs.id) FROM Seat hs WHERE hs.id IN (SELECT hc.seat_id FROM HoldContent hc WHERE hc.hold_id = $alias.id)) AS nb_seats")
        ->addSelect("(SELECT count(tck.seat_id) FROM Ticket tck LEFT JOIN tck.Transaction t LEFT JOIN t.HoldTransaction htr WHERE htr.hold_id = $alias.id AND tck.seat_id IS NOT NULL) AS nb_seated_tickets")
      ;
    
    return $q;
  }
  
  
    /**
     * Returns an instance of this class.
     *
     * @return object HoldTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Hold');
    }
}
