<?php
  if ( sfConfig::get('sf_web_debug', false) )
  {
    echo get_partial('global/get_tickets_pdf', array('tickets_html' => $content));
    return;
  }
  
  $pdf = new sfDomPDFPlugin();
  $pdf->setInput(get_partial('global/get_tickets_pdf', array('tickets_html' => $content)));

  // records the PDF as a file, and remember the name of that file
  $filename = sfConfig::get('sf_app_cache_dir').'/tickets-'.date('YmdHis').'-'.rand(1000000, 9999999).'.pdf';
  file_put_contents($filename, $pdf->render());
  echo exec('/usr/sbin/cupsfilter -e -m printer/pqueue -p /var/www/e-venement-git/data/cups/StarTSP700.ppd '.$filename.' 2> /dev/null');
?>
