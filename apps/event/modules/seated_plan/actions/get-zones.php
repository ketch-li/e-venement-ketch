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
*    Copyright (c) 2006-2017 Baptiste SIMON <baptiste.simon AT e-glop.net>
*    Copyright (c) 2006-2017 Libre Informatique [http://www.libre-informatique.fr/]
*
***********************************************************************************/
?>
<?php
    $this->preLinks($request);
    $id = $request->getParameter('id', false);
    
    $this->data = array(
        'type'  => 'zones',
        'zones' => array(),
    );
    
    if ( $id.'' === ''.intval($id) )
    {
        $q = Doctrine::getTable('SeatedPlanZone')->createQuery('z')
            ->andWhere('z.seated_plan_id = ?', $id)
        ;
        
        $zones = $q->fetchArray();
        foreach ( $zones as $zone ) {
            $this->data['zones'][] = array('id' => $zone->id, 'polygon' => json_decode($zone['zone'],true), 'color' => 'red');
        }
    }
    
    if ( sfConfig::get('sf_web_debug', false) && $request->hasParameter('debug') )
      return $this->renderText(print_r($this->data));
    return 'Success';