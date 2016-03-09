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
    $this->getContext()->getConfiguration()->loadHelpers('I18N');
    $this->transaction = Doctrine::getTable('Transaction')->fetchOneById($request->getParameter('id'));
    $payments = sfConfig::get('app_payments_list', array());
    
    // security checks
    if (!(
        $this->transaction instanceof Transaction
     && (sfConfig::get('app_payment_type', false) == 'onthespot' || isset($payments['onthespot']))
     && $this->transaction->contact_id == $this->getUser()->getContact()->id
     && ($this->transaction->BoughtProducts->count() > 0 || $this->transaction->Tickets->count() > 0 || $this->transaction->MemberCards->count() > 0 )
    ))
    {
      if (!( sfConfig::get('app_payment_type', false) == 'onthespot' || isset($payments['onthespot']) ))
        error_log('cart/onthespot: Someone tried to access the "onthespot" payment plugin, whereas it has not been enabled.');
      $this->getUser()->setFlash('notice', $str = __('Please control your order...'));
      error_log('cart/onthespot - Transaction #'.$this->getUser()->getTransactionId().': '.$str);
      $this->redirect('@homepage');
    }
    
    // recording the order
    $this->transaction->Order[] = new Order;
    $this->createPaymentsDoneByMemberCards();
    $this->transaction->save();
    
    // confirmation email
    $this->sendConfirmationEmails($this->transaction, $this);
    
    // starting a new transaction
    $this->getUser()->resetTransaction();
    
    // notices on screen
    if ( $this->transaction->Payments->count() > 0 )
      $this->getUser()->setFlash('notice',__("Your command has been passed on your member cards, you don't have to pay anything."));
    elseif ( sfConfig::get('app_payment_type', 'paybox') == 'onthespot' )
      $this->getUser()->setFlash('notice',__("Your command has been booked, you will have to pay for it directly with us."));
    
    // redirection
    $redirect = 'transaction/show?id='.$this->transaction->id;
    $this->redirect($redirect);