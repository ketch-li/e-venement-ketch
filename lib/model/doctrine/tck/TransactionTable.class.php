<?php
/**********************************************************************************
*
*	    This file is part of e-venement.
*
*    e-venement is free software; you can redistribute it and/or modify
*    it under the terms of the GNU General Public License as published by
*    the Free Software Foundation; either version 2 of the License.
*
*    e-venement is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU General Public License for more details.
*
*    You should have received a copy of the GNU General Public License
*    along with e-venement; if not, write to the Free Software
*    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
*    Copyright (c) 2006-2015 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2015 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php

/**
 * TransactionTable
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class TransactionTable extends PluginTransactionTable
{
    /**
     * Returns an instance of this class.
     *
     * @return object TransactionTable
     */
    public static function getInstance()
    {
        return Doctrine_Core::getTable('Transaction');
    }
  
  // $tickets can be NULL (all), 'asked' for asked tickets or an other not-empty-string for printed/integrated/cancelling tickets
  public function createQuery($alias = 't', $tickets = NULL, $with_products = false)
  {
    $tck = 'tck' != $alias ? 'tck' : 'tck2';
    $m   = 'm'   != $alias ? 'm'   : 'm2';
    
    // this is for $tickets == 'asked', for $tickets == something_else it becomes "NOT ($str)"
    $str = "$tck.printed_at IS NULL AND $tck.integrated_at IS NULL AND $tck.cancelling IS NULL";
    
    $q = parent::createQuery($alias);
    $a = $q->getRootAlias();
    $q->leftJoin("$a.Tickets $tck".(is_null($tickets) ? '' : ' WITH '.($tickets == 'asked' ? $str : "NOT ($str)")))
      ->leftJoin("$tck.Duplicatas duplicatas")
      ->leftJoin("$tck.Cancelled cancelled")
      ->leftJoin("$tck.Manifestation $m");
    if ( $with_products )
      $q->leftJoin("$a.BoughtProducts bp".(is_null($tickets) ? '' : ' WITH '.($tickets == 'asked' ? 'bp.integrated_at IS NULL' : "NOT ($str)")));
    return $q;
  }
  
  public function createQueryForStore($alias = 't', $culture = NULL)
  {
    $q = Doctrine_Query::create()->from('Transaction '.$alias)
      ->leftJoin("$alias.BoughtProducts bp")
      ->leftJoin('bp.Declination d')
      ->leftJoin('d.Translation dt WITH dt.lang '.($culture ? '=' : '!=').' ?', $culture)
      ->leftJoin('d.Product pdt')
      ->leftJoin('pdt.Translation pdtt WITH pdtt.lang '.($culture ? '=' : '!=').' ?', $culture)
      ->leftJoin('pdt.Category c')
      ->leftJoin('c.Translation ct WITH ct.lang '.($culture ? '=' : '!=').' ?', $culture)
      ->leftJoin('bp.Price price')
      ->leftJoin('pdt.Prices pdtp WITH pdtp.id = price.id')
    ;
    return $q;
  }
  
  public function createQueryForLineal($a = 't')
  {
    $q = parent::createQuery($a);
    $q->leftJoin("$a.Tickets tck ON tck.transaction_id = t.id AND tck.duplicating IS NULL AND (tck.printed_at IS NOT NULL OR tck.cancelling IS NOT NULL OR tck.integrated_at IS NOT NULL)")
      ->leftJoin("$a.Invoice i")
      ->leftJoin('tck.Manifestation m')
      ->leftJoin('m.Event e')
      ->orderBy("$a.updated_at, $a.id, tck.updated_at");
    return $q;
  }
  
  public function fetchOneById($id)
  {
    $q = $this->createQuery()
      ->andWhere('id = ?',$id);
    return $q->fetchOne();
  }
  public function findOneById($id)
  {
    return $this->fetchOneById($id);
  }
  
  public function retrieveDebtsList()
  {
    $q = Doctrine_Query::create()->from('Transaction t')
      ->leftJoin('t.Contact c')
      ->leftJoin('t.Professional p')
      ->leftJoin('p.ProfessionalType pt')
      ->leftJoin('p.Organism o')
      ->leftJoin('t.Invoice i')
    ;
    $this->setDebtsListCondition($q);
    return $q;
  }
  public static function setDebtsListCondition(Doctrine_Query $q, $dates = array('from' => NULL, 'to' => NULL))
  {
    self::addDebtsListBaseSelect($q)
      ->addSelect(str_replace(array('%%tck%%', '%%pdt%%'), array('tck', 'pdt'), $outcomes = '((SELECT (CASE WHEN COUNT(%%tck%%.id) = 0 THEN 0 ELSE SUM(%%tck%%.value + CASE WHEN %%tck%%.taxes IS NULL THEN 0 ELSE %%tck%%.taxes END) END) FROM Ticket %%tck%% WHERE '.self::getDebtsListTicketsCondition('%%tck%%', $dates['to'], $dates['from']).') + (SELECT (CASE WHEN COUNT(%%pdt%%.id) = 0 THEN 0 ELSE SUM(%%pdt%%.value) END) FROM BoughtProduct %%pdt%% WHERE '.self::getDebtsListProductsCondition('%%pdt%%', $dates['to'], $dates['from']).'))').' AS outcomes')
      ->addSelect(str_replace('%%pp%%' , 'pp' , $incomes  = '(SELECT (CASE WHEN COUNT(%%pp%%.id)  = 0 THEN 0 ELSE SUM(%%pp%%.value) END) FROM Payment %%pp%% WHERE %%pp%%.transaction_id = t.id '.($dates['from'] ? " AND %%pp%%.created_at >= '".$dates['from']."'" : '').($dates['to'] ? " AND %%pp%%.created_at < '".$dates['to']."'" : '').')').' AS incomes')
      ->where(str_replace(array('%%tck%%', '%%pdt%%'), array('tck2', 'pdt2'), $outcomes).' - '.str_replace('%%pp%%', 'p2', $incomes).' != 0');
    return $q;
  }
  public static function getDebtsListTicketsCondition($table = 'tck', $date = NULL, $from = NULL)
  {
    $r = "$table.transaction_id = t.id AND $table.duplicating IS NULL AND ($table.printed_at IS NOT NULL OR $table.integrated_at IS NOT NULL OR $table.cancelling IS NOT NULL)";
    if ( !is_null($date) )
      $r .= " AND ($table.cancelling IS NULL AND ($table.printed_at IS NOT NULL AND $table.printed_at < '$date' OR $table.integrated_at IS NOT NULL AND $table.integrated_at < '$date') OR $table.cancelling IS NOT NULL AND $table.created_at < '$date')";
    if ( !is_null($from) )
      $r .= " AND ($table.cancelling IS NULL AND ($table.printed_at IS NOT NULL AND $table.printed_at >= '$from' OR $table.integrated_at IS NOT NULL AND $table.integrated_at >= '$from') OR $table.cancelling IS NOT NULL AND $table.created_at >= '$from')";
    return $r;
  }
  public static function getDebtsListProductsCondition($table = 'pdt', $date = NULL, $from = NULL)
  {
    $r  = '';
    $r .= $table.'.transaction_id = t.id AND '.$table.'.integrated_at IS NOT NULL';
    if ( !is_null($date) )
      $r .= " AND $table.integrated_at < '$date'";
    if ( !is_null($from) )
      $r .= " AND $table.integrated_at >= '$from'";
    return $r;
  }
  public static function addDebtsListBaseSelect(Doctrine_Query $q)
  {
    return $q
      ->select($fields = 't.id, t.closed, t.updated_at, c.id, c.name, c.firstname, p.id, p.name, pt.id, pt.name, o.id, o.name, o.city, i.id')
      ->addSelect("'yummy' AS yummy") // a trick to avoid an obvious bug which removes the name of the field following directly the first ones (??)
    ;
  }
}
