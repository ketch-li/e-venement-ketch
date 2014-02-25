    <td class="name"><?php echo cross_app_link_to($transac->Contact,'rp','contact/show?id='.$transac->contact_id) ?> <span class="pictos"><?php if ( is_object($transac->Contact) ) echo $sf_data->getRaw('transac')->Contact->groups_picto ?></span></td>
    <td class="pro-groups"><?php echo $sf_data->getRaw('transac')->Professional->groups_picto ?></td>
    <td class="organism">
      <?php if ( $contact['pro'] ) echo cross_app_link_to($contact['pro']->Organism,'rp','organism/show?id='.$contact['pro']->Organism->id) ?>
      <?php echo $sf_data->getRaw('transac')->Professional->Organism->groups_picto ?>
    </td>
    <td class="tickets"><?php include_partial('show_spectators_list_tickets',array('tickets' => $ws, 'show_workspaces' => $show_workspaces)) ?></td>
    <td class="price"><?php echo format_currency($contact['value'][$wsid],'€') ?></td>
    <td class="accounting"><?php if ( $contact['transaction']->Invoice[0]->id ): ?>#<?php echo $contact['transaction']->Invoice[0]->id ?><?php else: ?>-<?php endif ?></td>
    <td class="transaction" title="<?php echo __('Updated at %%d%% by %%u%%',array('%%d%%' => format_datetime($transac->updated_at), '%%u%%' => $transac->User)) ?>">#<?php echo cross_app_link_to($transac->id,'tck','ticket/sell?id='.$transac->id) ?></td>
    <td class="ticket-ids"><?php include_partial('show_spectators_list_ids',array('tickets' => $contact['ticket-ids'][$wsid], 'show_workspaces' => $show_workspaces, 'num' => '#')) ?></span>
    <td class="ticket-nums"><?php include_partial('show_spectators_list_ids',array('tickets' => $contact['ticket-nums'][$wsid], 'show_workspaces' => $show_workspaces, 'num' => 'n°')) ?></span>
