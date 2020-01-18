<?php

namespace Drupal\cache_factorial\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form with the factorial function calculation.
 */
class CacheFactorialForm extends FormBase {
  /**
   * Cache object.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('cache.entity')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cache_factorial';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number'),
      '#required' => TRUE,
      '#description' => $this->t('Minimal number is 1 and maximum is 170'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $number = $form_state->getValue('number');

    if ($number > 170) {
      $form_state->setErrorByName('number', $this->t('Your number is too big'));
    }

    if ($number < 1) {
      $form_state->setErrorByName('number', $this->t('Your number is too low'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $number = $form_state->getValue('number');

    $cid = 'cache_factorial: lastcalculation';
    $calculation = NULL;
    if ($cache = $this->cache->get($cid)) {
      $calculation = $cache->data;
    }

    $cid = 'cache_factorial: lastnumber';
    $last_number = NULL;
    if ($cache = $this->cache->get($cid)) {
      $last_number = $cache->data;
    }

    if ($last_number == NULL||$calculation == NULL) {
      $calculation = $this->factorial(0, $number, 1, TRUE);
    }
    elseif ($last_number != $number) {
      $calculation = $this->factorial($last_number, $number, $calculation, $last_number < $number);
    }

    $cid = 'cache_factorial: lastcalculation';
    $this->cache->set($cid, $calculation);
    $cid = 'cache_factorial: lastnumber';
    $this->cache->set($cid, $number);

    $result = sprintf("%.0f", $calculation);
    $this->messenger()->addMessage($this->t('Factorial for @number is equal to the @result', ['@number' => $number, '@result' => $result]));
  }

  /**
   * Function to calculate factorial.
   */
  public function factorial($start, $end, $calculation, $grow) {
    $calculation = strval($calculation);
    if (!$grow) {
      $tmp = $start;
      $start = $end;
      $end = $tmp;
    }
    for ($i = $start + 1; $i <= $end; $i++) {
      if ($grow) {
        $calculation = strval($calculation) * strval($i);
      }
      else {
        $calculation = strval($calculation) / strval($i);
      }

    }
    return strval($calculation);
  }

}
