<?php include_partial('attendance/filters',array('form' => $form)) ?>
<div class="ui-widget ui-corner-all ui-widget-content">
  <div class="ui-widget-header ui-corner-all fg-toolbar">
    <?php include_partial('attendance/filters_buttons') ?>
    <h1><?php echo __('Ticketting activity',array(),'menu') ?></h1>
  </div>
  <?php include_partial('global/chart_jqplot', array(
		  	    'id'    => 'activity',
		  	    'data'  => cross_app_url_for('stats', 'activity/json'),
		  	    'width' => '900'
		  	  )) 
		?>
  <div class="chart">
    <?php echo liWidgetOfc::createChart(900, 530, $sf_context->getModuleName().'/data',true); ?>
  </div>
  <div class="actions"><?php include_partial('global/chart_actions') ?></div>
</div>

<?php use_javascript('/js/jqplot/plugins/jqplot.barRenderer.js') ?>
<?php use_javascript('/js/jqplot/plugins/jqplot.cursor.js') ?>
<?php use_javascript('/js/jqplot/plugins/jqplot.canvasAxisTickRenderer.js') ?>
<?php use_javascript('/js/jqplot/plugins/jqplot.canvasTextRenderer.js') ?>
<?php use_javascript('/js/jqplot/plugins/jqplot.dateAxisRenderer.js') ?>
<?php use_javascript('stats-activity?'.date('Ymd')) ?>