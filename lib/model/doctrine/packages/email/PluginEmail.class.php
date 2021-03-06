<?php

/**
 * PluginEmail
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    e-venement
 * @subpackage model
 * @author     Baptiste SIMON <baptiste.simon AT e-glop.net>
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */
abstract class PluginEmail extends BaseEmail
{
  public $not_a_test      = false;
  public $test_address    = NULL;
  public $to              = array();
  public $mailer          = NULL;
  public $from_txt        = NULL;
  protected $parts        = array('text' => NULL, 'html' => NULL);
  protected $embedded_images = 0;
  protected $message = NULL;
  protected $matcher = array();
  protected $cache = array();
  
  protected function isNewsletter()
  {
    return $this->Contacts->count() + $this->Professionals->count() + $this->Organisms->count() > 1;
  }
  
  protected function send()
  {
    if ( !$this->to )
      $this->to = array();
    if ( !is_array($this->to) )
      $this->to = array($this->to);
    
    // is this a newsletter or not ?
    
    // sending one by one to linked ...
    // contacts
    $this->matcher = array();
    foreach ( $this->Contacts as $contact )
    if ( $contact->email && !($this->isNewsletter() && $contact->email_no_newsletter) )
    {
      $this->to[] = trim($contact->email);
      $this->matcher[count($this->to)-1] = $contact;
    }
    // professionals
    foreach ( $this->Professionals as $pro )
    {
      if ( $pro->contact_email && !($this->isNewsletter() && $pro->contact_email_no_newsletter) )
        $this->to[] = trim($pro->contact_email);
      elseif ( $pro->Contact->email && !($this->isNewsletter() && $pro->Contact->email_no_newsletter) )
        $this->to[] = trim($pro->Contact->email);
      elseif ( $pro->Organism->email && !($this->isNewsletter() && $pro->Organism->email_no_newsletter) )
        $this->to[] = trim($pro->Organism->email);
      $this->matcher[count($this->to)-1] = $pro;
    }
    // organisms
    foreach ( $this->Organisms as $organism )
    if ( $organism->email && !($this->isNewsletter() && $organism->email_no_newsletter) )
    {
      $this->to[] = trim($organism->email);
      $this->matcher[count($this->to)-1] = $organism;
    }
    
    // concatenate addresses
    $this->field_to .= implode(', ',$this->to);
    return $this->raw_send(null,$this->nospool);
  }

  protected function sendTest()
  {
    if ( !$this->test_address )
      return false;
    
    return $this->raw_send(array($this->test_address),true);
  }
  
  private function return_bytes ($size_str)
  {
    switch (substr ($size_str, -1))
    {
        case 'K': case 'k': return (int)$size_str * 1024;
        case 'M': case 'm': return (int)$size_str * 1048576;
        case 'G': case 'g': return (int)$size_str * 1073741824;
        default: return $size_str;
    }
  }
  
  protected function raw_send($to = array(), $immediatly = false)
  {
    // sets the PHP timeout to 5 times the default parameter, to be able to process the sending correctly
    set_time_limit(ini_get('max_execution_time')*6);
    // sets the PHP memory_limit to twice the default parameter, to be able to process the sending correctly
    $limit = $this->return_bytes(ini_get('memory_limit'));
    if ( $limit > 0 && $limit < 1000000000 )
      ini_set('memory_limit', $limit*2);
    
    $to = is_array($to) && count($to) > 0 ? $to : $this->to;
    if ( !$to && !$this->field_to )
      return false;
    
    $this->message = Swift_Message::newInstance()->setTo($to);
    $this->compose();
    
    // sfEventDispatcher
    if ( sfContext::hasInstance() )
    {
      sfContext::getInstance()->getEventDispatcher()
        ->notify(new sfEvent($this, 'email.before_attach', $this->getDispatcherParameters()));
    }
    
    if ( $this->field_bcc )
      $this->message->setBcc($this->field_bcc);
    
    if ( $this->field_cc )
      $this->message->setCc($this->field_cc);
    
    // attach normal file attachments
    foreach ( $this->Attachments as $key => $attachment )
    {
      $id = $attachment->getId() ? $attachment->getId() : date('YmdHis').rand(10000,99999);
      if (!( $content = $attachment->content ))
      {
        unset($this->Attachments[$key]);
        error_log('PluginEmail: attachment #'.$attachment->id.' not found for email #'.$this->id.', continuing.');
        continue;
      }
      $att = Swift_Attachment::newInstance($content, $attachment->original_name, $attachment->mime_type)
        ->setId('part.'.$id.'@e-venement')
        //->setDisposition('inline')
      ;
      $this->message->attach($att);
    }
    
    // force setting the Content-Type to 'multipart/related' to really follow the norm
    if ( $this->embedded_images > 0 )
      $this->message->setContentType('multipart/related');
    
    $this->setMailer();
    $this->mailer->setMatcher($this->matcher);
    
    return $immediatly === true
      ? $this->mailer->sendNextImmediately()->send($this->message)
      : $this->mailer->batchSend($this->message);
  }
  
  public function setMailer(sfMailer $mailer = NULL)
  {
    if ( $this->mailer instanceof sfMailer )
      return $this;
    
    if ( $mailer instanceof sfMailer )
      $this->mailer = $mailer;
    elseif ( sfContext::hasInstance() )
      $this->mailer = sfContext::getInstance()->getMailer();
    
    if ( method_exists($this->mailer, 'setEmail') )
      $this->mailer->setEmail($this);
    
    return $this;
  }
  
  public function getFormattedContent()
  {
    if ( isset($this->cache['formatted_content']) )
      return $this->cache['formatted_content'];
    
    // process inline images
    $post_treated_content = $this->content;
    preg_match_all('!<img\s(.*)src="data:(image/\w+);base64,(.*)"(.*)/>!U', $post_treated_content, $imgs, PREG_SET_ORDER);
    foreach ( $imgs as $i => $img )
    {
      $att = Swift_Attachment::newInstance()
        ->setFileName("img-$i.".str_replace('image/', '', $img[2]))
        ->setContentType($img[2])
        ->setDisposition('inline')
        ->setBody(base64_decode($img[3]))
        ->setId("img$i.$i@e-venement")
      ;
      
      // embedding the image
      $post_treated_content = str_replace(
        $img[0],
        '<img '.$img[1].' '.$img[4].' src="'.$this->message->embed($att).'" />',
        $post_treated_content
      );
      
      $this->embedded_images++;
    }
    
    // process links
    if ( $this->id )
    {
      sfContext::getInstance()->getConfiguration()->loadHelpers('CrossAppLink');
      preg_match_all('!<a\s(.*)href="(http.*)"(.*)>(.*)</a>!U', $post_treated_content, $links, PREG_SET_ORDER);
      foreach ( $links as $link )
      if ( !preg_match('!\w{3,}\.\w{2,}$!', $link[4]) ) // avoid links having the a single URL in their text, which is considered as spam
      {
        $el = new EmailExternalLink;
        $el->original_url = $link[2];
        $el->encrypted_uri = md5($link[2].'|'.sfConfig::get('app_salt','').'|'.microtime());
        $el->email_id = $this->id;
        $el->save();
        
        $post_treated_content = str_replace(
          $link[0],
          '<a '.$link[1].'href="'.str_replace('https','http',cross_app_url_for('email', 'link/follow', true)).'?u='.$el->encrypted_uri.'&e=%%EMAILADDRESS%%"'.$link[3].'>'.$link[4].'</a>',
          $post_treated_content
        );
      }
      
      // adds a tracking external image
      $post_treated_content .= '<img src="'.str_replace('https','http',cross_app_url_for('email','track/index?i='.$this->id.'&s='.md5(sfConfig::get('app_salt','').'|'.microtime()),true)).'&e=%%EMAILADDRESS%%" alt=" " width="1" height="1" />';
    }
    
    $content =
      '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.
      '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">'.
      '<head>'.
      '<title>'.$this->field_subject.'</title>'.
      '<meta name="title" content="'.$this->field_subject.'" />'.
      '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'.
      '</head><body>'.
      $post_treated_content.
      '</body></html>';
    
    $this->cache['formatted_content'] = $content;
    return $content;
  }
  
  public function addParts($content = NULL)
  {
    $h2t = new HtmlToText($content ? $content : $this->getFormattedContent());
    $this->message
      ->attach($this->parts['html'] = Swift_MimePart::newInstance($h2t->get_html(), 'text/html'))
      ->attach($this->parts['text'] = Swift_MimePart::newInstance($h2t->get_text(), 'text/plain'))
    ;
    return $this;
  }
  
  public function removePart($type)
  {
    if ( !in_array($type, array('text', 'html')) )
      return $this;
    $this->message->detach($this->parts[$type]);
    return $this;
  }
  
  public function getMessage()
  {
    return $this->message;
  }

  protected function compose()
  {
    $this->addParts();
    $this->message
      ->setFrom(array($this->field_from => $this->from_txt ? $this->from_txt : $this->field_from))
      ->setSubject($this->field_subject)
    ;
    
    if ( $reply = sfConfig::get('project_email_replyto', false) ) 
      $this->message->setReplyTo($reply);
    
    if ( $this->read_receipt )
      $this->message->setReadReceiptTo($this->field_from);
    return $this->message;
  }
}
