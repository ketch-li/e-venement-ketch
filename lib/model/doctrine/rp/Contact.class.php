<?php

/**
 * Contact
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class Contact extends PluginContact
{
  protected $module = 'contact';
  protected $yobs_ordered = false;

  public function __toString()
  {
    if ( !sfConfig::get('app_case_normalise') )
      return $this->name.' '.$this->firstname;
    else
      return strtoupper($this->name).' '.ucwords(strtolower($this->firstname));
  }

  public function getFormattedName()
  {
    return ucfirst($this->firstname).' '.strtoupper($this->name);
  }
  public function getCoolname()
  {
    return ucfirst($this->firstname).' '.ucfirst($this->name);
  }
  public function getNameWithTitle()
  {
    return $this->title.' '.$this->formatted_name;
  }
  public function getFullAddress()
  {
    $addr = array();
    $addr[] = $this->address;
    $addr[] = $this->postalcode.' '.$this->city;
    $addr[] = $this->country;

    foreach ( $addr as $key => $line )
    if ( !trim($line) )
      unset($addr[$key]);

    return implode("\n", $addr);
  }

  public function getDepartment()
  {
    if ( trim(strtolower($this->country)) !== 'france' && $this->country || !$this->postalcode )
      return false;

    return Doctrine::getTable('GeoFrDepartment')->fetchOneByNumCP(substr($this->postalcode,0,2));
  }
  public function getRegion()
  {
    if ( $dpt = $this->getDepartment() )
      return $dpt->Region;
    else
      return false;
  }

  public function getYOBsString()
  {
    $arr = array();
    foreach ( $this->orderYOBs()->YOBs as $YOB )
      $arr[] = (string)$YOB;
    return implode(', ',$arr);
  }
  public function orderYOBs()
  {
    if ( $this->yobs_ordered )
      return $this;

    $arr = array();
    foreach ( $this->YOBs as $YOB )
      $arr[$YOB->year.$YOB->month.$YOB->day.$YOB->name.$YOB->id] = $YOB;
    ksort($arr);

    $this->YOBs->clear();
    foreach ( $arr as $YOB )
      $this->YOBs[] = $YOB;

    $this->yobs_ordered = true;
    return $this;
  }

  public function getIdBarcoded()
  {
    $c = ''.$this->id;
    $n = strlen($c);
    for ( $i = 12-$n ; $i > 0 ; $i-- )
      $c = '0'.$c;
    return $c;
  }

  /**
   * @see PluginContact::getVcard()
   **/
  public function getVcard($dummy = NULL)
  {
    return parent::getVcard();
  }

  /**
   * @see PluginContact::setVcard()
   **/
  public function setVcard($vcard, $dummy = NULL)
  {
    if (!( $vcard instanceof liVCard ))
      $vcard = new liVCard(NULL, $vcard);
    return parent::setVcard($vcard);
  }

  public function getGroupsPicto()
  {
    $str = '';
    foreach ( $this->Groups as $group )
    {
      if ( !sfContext::hasInstance() )
      {
        $str .= $group->getHtmlTag().' ';
        continue;
      }

      $sf_user = sfContext::getInstance()->getUser();
      $users = array();
      foreach ( $group->Users as $user )
        $users[] = $user->id;
      if ( $group->sf_guard_user_id == $sf_user->getId()
        || is_null($group->sf_guard_user_id) && (in_array($sf_user->getId(), $users) || $sf_user->hasCredential(array('admin','super-admin'),false)) )
        $str .= $group->getHtmlTag().' ';
    }
    return $str;
  }

  /**
    * Calculates the standard deviation and the average of bought tickets (excluding free seating tickets)
    *
    * @param  integer   (optional) a MetaEvent->id
    * @return array     an array componed by the average ('avg' index), the standard deviation ('std-dev' index) and the qty of seated tickets ('qty' index)
    *
    **/
  public function getStatsSeatRank($meta_event_id = NULL)
  {
    try { return $this->getFromCache('avg-seat-rank-'.$meta_event_id); }
    catch ( liEvenementException $e )
    {
      $data = Doctrine::getTable('Ticket')->createQueryPreparedForRanks('tck')
        ->andWhere('tck_t.contact_id = ? OR tck.contact_id = ?', array($this->id, $this->id))
        ->select('AVG(tck_s.rank) AS avg')
        ->addSelect('stddev_pop(tck_s.rank) AS std_dev')
        ->addSelect('count(tck.id) AS qty')
        ->fetchArray();

      return $this->setInCache('avg-seat-rank-'.$meta_event_id, array(
        'avg'     => floatval($data[0]['avg']),
        'std-dev' => floatval($data[0]['std_dev']),
        'qty'     => intval($data[0]['qty']),
      ))->getStatsSeatRank($meta_event_id);
    }
  }
  
  public function getActiveMembercards()
  {
    return Doctrine::getTable('MemberCard')->createQuery('mc')
      ->andWhere('mc.contact_id = ?', $this->id)
      ->andWhere('mc.active = true')
      ->andWhere('mc.expire_at >= now()')
      ->execute();
  }
}
