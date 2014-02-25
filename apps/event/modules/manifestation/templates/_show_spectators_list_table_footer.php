<?php use_helper('Number') ?>
<tfoot>
  <tr>
    <td class="name">Total</td>
    <td class="pro-groups"></td>
    <td class="organism"></td>
    <td class="tickets"><?php $qty = 0; foreach ( $total['qty'] as $value ) $qty += $value; echo $qty; ?></td>
    <td class="price"><?php echo format_currency($total['value'],'€') ?></td>
    <td class="transaction">-</td>
    <td class="accounting">-</td>
    <td class="ticket-ids">-</td>
    <td class="ticket-nums"><?php if ( $sf_user->hasCredential('seats-allocation') ): ?>-<?php endif ?></td>
  </tr>
</tfoot>
