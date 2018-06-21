<?php

namespace Drupal\kgaut_emails;

use Drupal\Core\Url;

class Email {

  private $sitename;
  private $langcode;
  private $module;
  private $key;
  private $to;
  private $subject;
  private $reply;
  private $send;
  private $params;
  private $data;


  public function __construct($key, $to, $subject, $data = [], $from = NULL) {
    $this->key = $key;
    $this->to = $to;
    $this->data = $data;
    $this->subject = $subject;
    $this->langcode = \Drupal::config('system.site')->get('langcode');
    $this->module = 'kgaut_emails';
    $this->key = $key;

    if (!isset($data['from'])) {
      $data['from'] = \Drupal::config('system.site')->get('mail');
    }
    if(filter_var($this->to, FILTER_VALIDATE_EMAIL) && $to = user_load_by_mail($this->to)) {
      $this->data['to'] = [
        'account' => $to,
        'firstname' => $to->get('firstname')->value,
        'lastname' => $to->get('lastname')->value,
        'url' => Url::fromRoute('entity.user.canonical', ['user' => $to->id()], ['absolute' => TRUE]),
      ];
      $this->to = $to->getEmail();
    }
    if(filter_var($data['from'], FILTER_VALIDATE_EMAIL) && $from = user_load_by_mail($data['from']))  {
      $this->data['from'] = [
        'account' => $from,
        'firstname' => $from->get('firstname')->value,
        'lastname' => $from->get('lastname')->value,
        'url' => Url::fromRoute('entity.user.canonical', ['user' => $from->id()], ['absolute' => TRUE]),
      ];
    }
    $this->params['message'] = ['#theme' => $key, '#data' => $this->data];
    $this->params['subject'] = $subject;
  }

  public function getMessage() {
    return $this->params['message'];
  }


  public function renderMail() {
    $source = \Drupal::service('renderer')->renderPlain($this->params['message']);
    $this->params['message'] = $source;
  }


  public function send() {
    $this->send = TRUE;
    $this->renderMail();
    $mailManager = \Drupal::service('plugin.manager.mail');
    $mailManager->mail($this->module, $this->key, $this->to, $this->langcode, $this->params, $this->reply, $this->send);
  }

}
