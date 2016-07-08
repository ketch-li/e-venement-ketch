<?php

/**
 * PluginTransaction
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginTransaction extends BaseTransaction
{
  public function getIndexesPrefix()
  {
    return strtolower(get_class($this));
  }
  
  public function preSave($event)
  {
    parent::preSave($event);
    
    if ( !$this->contact_id || in_array('contact_id', $this->_modified) && !in_array('professional_id', $this->_modified) )
      $this->professional_id = NULL;
    
    // hardening contact_id within member cards
    // this aims to avoid the non-affectation of the contact_id on a member card
    // during online sales where the customer creates its account at the end of the process
    if ( $this->contact_id )
    foreach ( $this->MemberCards as $mc )
    if ( !$mc->contact_id )
      $mc->contact_id = $this->contact_id;
  }
  
  public function preInsert($event)
  {
    parent::preInsert($event);
    
    if ( sfConfig::has('app_transaction_with_shipment') )
      $this->with_shipment = (bool)sfConfig::get('app_transaction_with_shipment');
  }
}
