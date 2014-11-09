<?php

/**
 * SeatedPlan
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
class SeatedPlan extends PluginSeatedPlan
{
  public function render(array $gauges, array $attributes = array())
  {
    sfApplicationConfiguration::getActive()->loadHelpers(array('CrossAppLink'));
    
    // default values
    foreach ( array(
      'app' => 'event',
      'get-seats' => 'seated_plan/getSeats',
      'on-demand' => false,
      'match-seated-plan' => true,
      'add-data-src' => false,
    ) as $key => $value )
    if ( !isset($attributes[$key]) )
      $attributes[$key] = $value;
    
    $img = $this->Picture->render(array(
      'title' => $this->Picture,
      'width' => $this->ideal_width ? $this->ideal_width : '',
      'app'   => $attributes['app'],
      'add-data-src' => $attributes['add-data-src'],
    ));
    
    $data = '';
    foreach ( $gauges as $gauge )
      $data .= '<a
        href="'.cross_app_url_for($attributes['app'], $attributes['get-seats'].'?gauge_id='.$gauge->id.($attributes['match-seated-plan'] ? '&id='.$this->id : '')).'"
        class="seats-url"
        data-gauge-id="'.$gauge->id.'"
      ></a>';
    
    return '<span
      id="plan-'.$this->id.(count($gauges) > 0 ? '-manif-'.$gauges[0]->Manifestation->id : '').'"
      class="seated-plan picture '.($attributes['on-demand'] ? 'on-demand' : '').'"
    >'.$img.$data.'</span>';
  }
  
  public function clearLinks()
  {
    $q = Doctrine::getTable('SeatLink')->createQuery('sl')
      ->where('sl.seat1 IN (SELECT s1.id FROM Seat as s1 WHERE s1.seated_plan_id = ?)', $this->id)
      ->orWhere('sl.seat2 IN (SELECT s2.id FROM Seat as s2 WHERE s2.seated_plan_id = ?)', $this->id)
      ->delete();
    $q->execute();
    return $this;
  }
  
  public function getLinks()
  {
    $links = array();
    
    foreach ( Doctrine::getTable('Seat')->createQuery('s')
      ->leftJoin('s.Neighbors n')
      ->andWhere('s.seated_plan_id = ?',$this->id)
      ->orderBy('s.name')
      ->execute() as $seat )
    foreach ( $seat->Neighbors as $neighbor )
    if ( !isset($links[$seat->id.'++'.$neighbor->id]) && !isset($links[$neighbor->id.'++'.$seat->id]) )
      $links[$seat->id.'++'.$neighbor->id] = array(
        $seat,
        $neighbor,
      );
    
    return $links;
  }
}
