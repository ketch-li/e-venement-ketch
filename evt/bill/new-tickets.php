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
*    Copyright (c) 2006 Baptiste SIMON <baptiste.simon AT e-glop.net>
*
***********************************************************************************/
?>
<?php
  require('conf.inc.php');
  
  includeClass('tickets');
  
  // vérifs
  if ( ($transac = intval($_GET['transac'])) <= 0 )
  {
    $user->addAlert("Problème dans le numéro d'opération transmis au bon de commande.");
    $nav->redirect('.');
  }
  
  $group = isset($_GET['group']);
  $tarif = isset($_GET['tarif']) ? $_GET['tarif'] : false;
  $manifid = intval($_GET['manifid']) ? $_GET['manifid'] : false;
  
  function verif_transaction()
  {
    global $bd,$user,$nav;
    if ( !$bd->getTransactionStatus() )
    {
      $bd->endTransaction();
      $user->addAlert('Impossible de retrouver les informations relatives au billet en base...');
      $nav->redirect('evt/bill/');
    }
  }
  
  $bd->beginTransaction();
  
  // récup des infos sur la personne
  $query  = ' SELECT p.id, p.prenom, p.nom, p.adresse, p.cp, p.ville, p.pays, p.email
              FROM transaction AS t
              LEFT JOIN personne_properso p
                     ON p.id = t.personneid
                    AND ( p.fctorgid = t.fctorgid OR t.fctorgid IS NULL AND p.fctorgid IS NULL )
              WHERE t.id = '.$transac;
  $request = new bdRequest($bd,$query);
  $personne = $request->getRecord();
  $request->free();
  if ( intval($personne['id']) <= 0 )
  {
    $user->addAlert('Impossible de faire un bon de commande pour une personne inconnue.');
    $nav->redirect('.');
  }
  
  // canceling reservation_cur old tickets for duplicatas
  $duplicata = false;
  if ( $tarif && $manifid )
  {
    $where  = '     tm.id  = p.tarifid
                AND tm.manifid = p.manifid
                AND c.resa_preid = p.id
                AND NOT canceled
                AND p.transaction = '.$transac;
    if ( $tarif )
    $where .= " AND tm.key ILIKE '".pg_escape_string($tarif)."'";
    if ( $manifid )
    $where .= ' AND p.manifid = '.$manifid;
    $using  = 'reservation_pre p, tarif_manif tm';
    $query = ' SELECT count(*) AS nb
               FROM reservation_cur c, '.$using.'
               WHERE '.$where;
    $request = new bdRequest($bd,$query);
    $existing = $request->getRecord('nb'); // for existing records
    $request->free();
    $updated = $bd->updateRecords('reservation_cur c',$where,array('canceled' => 't'),$using); // for updates
    $duplicata = $existing == $updated; // are they duplicatas
  }
  
  verif_transaction();
  
  // récup des infos sur les billets
  $select   = 'e.nom, e.petitnom, m.date, m.txtva, m.id AS manifid, e.metaevt,
               s.nom AS sitenom, s.cp, s.ville, s.pays,
               tm.description AS tarif, tm.prix, tm.prixspec,
               r.plnum, r.transaction AS transac';
  $selectnb = ', count(*) AS nb';
  $groupby  = 'GROUP BY e.nom, e.petitnom, m.date, m.txtva, m.id, e.metaevt,
                        s.nom, s.cp, s.ville, s.pays,
                        tm.description, tm.prix, tm.prixspec,
                        r.plnum, r.transaction ';
  $where    = '     m.id = r.manifid
                AND e.id = m.evtid
                AND s.id = m.siteid
                AND tm.id = r.tarifid
                AND r.transaction = '.$transac.'
                AND tm.manifid = m.id
                AND tm.id = r.tarifid
                AND r.id NOT IN ( SELECT resa_preid FROM reservation_cur WHERE NOT canceled )
                '.($tarif ? "AND tm.key ILIKE '".$tarif."'" : '').'
                '.($manifid ? 'AND r.manifid = '.$manifid : '');
  $orderby  = ' e.nom, m.date, s.ville, s.nom, tm.prix';
  $from = 'manifestation m, reservation_pre r, evenement e, site s, tarif_manif tm';
  $query  = ' SELECT '.$select.'
                     '.($group ? $selectnb : '').'
              FROM   '.$from.'
              WHERE  '.$where.'
              '.($group ? $groupby : '').'
              ORDER BY '.$orderby;
  $request = new bdRequest($bd,$query);
  
  verif_transaction();
  
  $correspondance = array(
    'date'      => 'date',
    'manifid'   => 'manifid',
    'metaevt'   => 'metaevt',
    'sitenom'   => 'sitenom',
    'prix'      => 'prix',
    'evtnom'    => 'nom',
    'createurs' => 'createurs',
    'org'       => 'org',
    'orga'      => 'orga',
    'plnum'     => 'plnum',
    'num'       => 'transac',
    'operateur' => 'userid',
    'nbgroup'   => 'nb',
  );
  
  $tickets = new Tickets($group);
  
  while ( $rec = $request->getRecordNext() )
  {
    $rec['prix']        = round($rec['prixspec'] ? $rec['prixspec'] : $rec['prix'],2);
    $rec['createurs']   = array();
    if ( $rec['organisme1'] )
    $rec['createurs'][] = $rec['organisme1'];
    if ( $rec['organisme2'] )
    $rec['createurs'][] = $rec['organisme2'];
    if ( $rec['organisme3'] )
    $rec['createurs'][] = $rec['organisme3'];
    $rec['createurs']   = implode(', ',$rec['createurs']);
    $rec['userid']      = $user->getId();
    $rec['nom']         = $rec['petitnom'] ? $rec['petitnom'] : $rec['nom'];
    $rec['nb']          = $rec['nb'] ? $rec['nb'] : 1;
    
    // les co-org.
    $query  = ' SELECT o.nom
                FROM manif_organisation mo, organisme o
                WHERE o.id = mo.orgid
                  AND mo.manifid = '.$rec['manifid'];
    $orgs = new bdRequest($bd,$query);
    $rec['orga'] = array();
    while ( $org = $orgs->getRecordNext() )
      $rec['orga'][] = $org['nom'];
    $orgs->free();
    $rec['org'] = implode(', ',$rec['orga']);
    
    $bill = array();
    foreach ( $correspondance as $key => $value )
      $bill[$key] = $rec[$value];
    if ( $duplicata )
      $bill['info']        = 'duplicata';
    if ( $annulation )
      $bill['info']        = 'annulation';
    
    $tickets->addToContent($bill);
  }
  
  $request->free();
  
  // si tout est ok, on met les modifs en base
  $from   = ' reservation_pre p';
  $where  = '    transaction = '.$transac.'
             AND p.id NOT IN ( SELECT resa_preid FROM reservation_cur WHERE NOT canceled ) ';
  if ( $tarif )
  {
    $from   .= ', tarif_manif tm';
    $where  .= "AND p.tarifid = tm.id
                AND p.manifid = tm.manifid
                AND tm.key ILIKE '".$tarif."' ";
  }
  if ( $manifid )
    $where  .= 'AND p.manifid = '.$manifid;
  $query  = ' SELECT p.*
              FROM '.$from.'
              WHERE '.$where;
  $request = new bdRequest($bd,$query);
  while ( $pre = $request->getRecordNext() )
  {
    $cur = array(
      'accountid'   => $user->getId(),
      'resa_preid'  => intval($pre['id']),
    );
    $bd->addRecord('reservation_cur',$cur);
  }
  
  verif_transaction();
  $bd->endTransaction();
  
  $tickets->printAll();
  $bd->free();
?>
