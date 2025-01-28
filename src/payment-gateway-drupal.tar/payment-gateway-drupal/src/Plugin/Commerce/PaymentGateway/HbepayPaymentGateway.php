<?php

namespace Drupal\hbepay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\Order;


/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "hbepay_redirect",
 *   label = "HB Epay Payment Gateway",
 *   display_label = @Translation("hbepay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\hbepay\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 * )
 */
class HbepayPaymentGateway extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['Description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#default_value' => $this->configuration['Description'],
      '#required' => FALSE,
    ];
    $form['Client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client id'),
      '#default_value' => $this->configuration['Client_id'],
      '#required' => TRUE,
    ];
    $form['Client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $this->configuration['Client_secret'],
      '#required' => TRUE,
    ];
    $form['Terminal'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Terminal'),
      '#default_value' => $this->configuration['Terminal'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['Description'] = $values['Description'];
      $this->configuration['Client_id'] = $values['Client_id'];
      $this->configuration['Client_secret'] = $values['Client_secret'];
      $this->configuration['Terminal'] = $values['Terminal'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        /** @var \Drupal\commerce_payment\Entity\Payment $payment */
            $payment = $payment_storage->create([
               'state' => 'completed',
               'amount' => $order->getTotalPrice(),
               'payment_gateway' => $this->entityId,
               'order_id' => $order->id(),
               'test' => $this->getMode(),
               'remote_id' => $order->id(),
               'remote_state' => 'completed'
             ]);
             $payment->save();
  }
}
