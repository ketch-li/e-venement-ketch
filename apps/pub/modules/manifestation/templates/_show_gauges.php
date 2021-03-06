<?php $conf = sfConfig::get('app_tickets_vel', array()) ?>

<?php echo $form->renderFormTag(url_for('cart/show'), array('class' => 'adding-tickets')) ?>
<a href="<?php echo url_for('ticket/getOrphans') ?>" id="ajax-pre-submit"></a>
<a href="<?php echo url_for('ticket/modTickets?manifestation_id='.$manifestation->id) ?>" id="ajax-init-data"></a>
<?php $available = 0 ?>

<?php foreach ( $gauges as $gauge ): ?>
<div class="gauge <?php if ( isset($conf['full_seating_by_customer']) && $conf['full_seating_by_customer'] ): ?>full-seating<?php endif ?>" id="gauge-<?php echo $gauge->id ?>" data-gauge-id="<?php echo $gauge->id ?>">
  <div class="blank"></div>
  <?php $form->setGaugeId($gauge->id) ?>
  <?php if ( $gauges->count() > 1 || isset($show_name) && $show_name ): ?>
    <h3><?php echo $gauge ?></h3>
  <?php endif ?>
  <?php if (( $free = $gauge->value
    - $gauge->printed
    - $gauge->ordered
    - (sfConfig::get('app_tickets_count_demands',false) ? $gauge->asked : 0)
    - (isset($vel['no_online_limit_from_manifestations']) && $vel['no_online_limit_from_manifestations'] ? 0 : $manifestation->online_limit)
  ) > 0 ): ?>
    <?php include_partial('show_prices',array('gauge' => $gauge, 'free' => $free, 'form' => $form, 'mcp' => $mcp, )) ?>
    <?php include_partial('show_gauge_picture',array('gauge' => $gauge, 'manifestation' => $manifestation)) ?>
    <?php $available++ ?>
  <?php endif ?>
</div>
<?php endforeach ?>

<?php if ( $available == 0 ): ?>
  <?php include_partial('show_full') ?>
<?php endif ?>

<?php use_javascript('pub-totals?'.date('Ymd')) ?>
</form>
