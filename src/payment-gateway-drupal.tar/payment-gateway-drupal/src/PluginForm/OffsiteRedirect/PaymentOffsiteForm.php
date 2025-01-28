<?php

namespace Drupal\hbepay\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;

class PaymentOffsiteForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {


    $form = parent::buildConfigurationForm($form, $form_state);
    return $this->buildRedirectForm($form, $form_state, $this-> pg_redirect($form));
  }

  public function pg_redirect($form){

    $payment = $this->entity;
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $payment_configuration = $payment_gateway_plugin->getConfiguration();
    $order = $payment->getOrder();

    $test_url = "https://testoauth.homebank.kz/epay2/oauth2/token";
    $prod_url = "https://epay-oauth.homebank.kz/oauth2/token";
    $test_page = "https://test-epay.homebank.kz/payform/payment-api.js";
    $prod_page = "https://epay.homebank.kz/payform/payment-api.js";

    $token_api_url = "";
    $pay_page = "";
    $err_exist = false;
    $err = "";

    // initiate default variables
    $hbp_account_id = "";
    $hbp_telephone = "";
    $hbp_email = "";
    $hbp_language = "EN";
    $hbp_description = $payment_configuration['Description'] ? $payment_configuration['Description'] : 'HB epay payment gateway';
    $hbp_env = $payment_configuration['mode'];
    $hbp_client_id = $payment_configuration['Client_id'];
    $hbp_client_secret = $payment_configuration['Client_secret'];
    $hbp_terminal = $payment_configuration['Terminal'];
    $hbp_invoice_id = '0800000' . $order->id();
    $hbp_amount = \Drupal::getContainer()->get('commerce_price.rounder')->round($payment->getAmount())->getNumber();

    $hbp_currency = $payment->getAmount()->getCurrencyCode();
    
    $hbp_back_link = $form['#return_url'];;
    $hbp_failure_back_link = $form['#cancel_url'];;
    $hbp_post_link = '';
    $hbp_failure_post_link = '';
    
    if ($hbp_env === 'test') {
        $token_api_url = $test_url;
        $pay_page = $test_page;
    } else {
        $token_api_url = $prod_url;
        $pay_page = $prod_page;
    }

    
    $fields = [
        'grant_type'      => 'client_credentials', 
        'scope'           => 'payment usermanagement',
        'client_id'       => $hbp_client_id,
        'client_secret'   => $hbp_client_secret,
        'invoiceID'       => $hbp_invoice_id,
        'amount'          => $hbp_amount,
        'currency'        => $hbp_currency,
        'terminal'        => $hbp_terminal,
        'postLink'        => $hbp_post_link,
        'failurePostLink' => $hbp_failure_post_link
      ];
    
      $fields_string = http_build_query($fields);
    
      $ch = curl_init();
    
      curl_setopt($ch, CURLOPT_URL, $token_api_url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
    
      $result = curl_exec($ch);
    
      $json_result = json_decode($result, true);
      if (!curl_errno($ch)) {
        switch ($http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
            case 200:
                $hbp_auth = (object) $json_result;
    
                $hbp_payment_object = (object) [
                    "invoiceId" => $hbp_invoice_id,
                    "backLink" => $hbp_back_link,
                    "failureBackLink" => $hbp_failure_back_link,
                    "postLink" => $hbp_post_link,
                    "failurePostLink" => $hbp_failure_post_link,
                    "language" => $hbp_language,
                    "description" => $hbp_description,
                    "accountId" => $hbp_account_id,
                    "terminal" => $hbp_terminal,
                    "amount" => $hbp_amount,
                    "currency" => $hbp_currency,
                    "auth" => $hbp_auth,
                    "phone" => $hbp_telephone,
                    "email" => $hbp_email
                ];
            ?>
            <script src="<?=$pay_page?>"></script>
            <script>
                halyk.pay(<?= json_encode($hbp_payment_object) ?>);
            </script>
        <?php
                break;
            default:
                echo 'Неожиданный код HTTP: ', $http_code, "\n";
        }
    }
  }


}
