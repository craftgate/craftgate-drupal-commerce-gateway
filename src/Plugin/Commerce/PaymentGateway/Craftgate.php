<?php

namespace Drupal\commerce_craftgate\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_price\Price;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Provides the Craftgate offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "craftgate",
 *   label = @Translation("Craftgate"),
 *   display_label = @Translation("Craftgate"),
 *   modes = {
 *     "test" = @Translation("Sandbox"),
 *     "live" = @Translation("Live"),
 *   },
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_craftgate\PluginForm\Craftgate\PaymentForm",
 *     "refund-payment" = "Drupal\commerce_craftgate\PluginForm\Craftgate\PaymentRefundForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class Craftgate extends OffsitePaymentGatewayBase implements SupportsRefundsInterface
{

  public $craftgateClient;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);
    $this->init();
  }

  public function __wakeup()
  {
    $this->init();
  }

  protected function init()
  {
    $url = $this->getMode() == 'test' ? 'https://sandbox-api.craftgate.io' : 'https://api.craftgate.io';
    $this->craftgateClient = new \Craftgate\Craftgate(
      array(
        'apiKey' => $this->configuration['api_key'],
        'secretKey' => $this->configuration['secret_key'],
        'baseUrl' => $url,
      )
    );
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('This is the api key from the Craftgate panel.'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('The secret key from the Craftgate panel.'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE,
    ];

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['api_key'] = $values['api_key'];
    $this->configuration['secret_key'] = $values['secret_key'];
  }

  public function onReturn(OrderInterface $order, Request $request)
  {
    $token = $request->request->get('token');

    if ($token == null) {
      throw new PaymentGatewayException(t("Post data didn't have 'token' information."));
    }
    $response = json_decode($this->craftgateClient->payment()->retrieveCheckoutPayment($token));
    if ($response->errors ?? false) {
      throw new PaymentGatewayException($response->errors->errorDescription);
    }
    if ($response->data->paymentStatus != 'SUCCESS') {
      throw new PaymentGatewayException(t('Payment status of @status is not expected.', ['@status' => $response->data->paymentStatus]));
    }
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'completed',
      'amount' => new Price($response->data->paidPrice, $response->data->currency),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $response->data->id
    ]);
    $payment->save();
    if ($response->data->cardUserKey ?? false) {
      $user = $order->getCustomer();
      $this->setRemoteCustomerId($user, $response->data->cardUserKey);
      $user->save();
    }
  }

  public function refundPayment(PaymentInterface $payment, ?Price $amount = NULL)
  {
    // Craftgate only allows refund by order item/transaction so the specified order item/transaction must be sent.
    // In order to achive that we provide another method called refundPaymentByTransaction and perform refund there.
    // Also PaymentRefundForm is overriden to use refundPaymentByTransaction method
  }

  public function refundPaymentByTransaction(PaymentInterface $payment, $transaction)
    {
        $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
        // Perform the refund request here, throw an exception if it fails.
        try {
            $request = array(
                'paymentTransactionId' => $transaction['id'],
                'refundPrice' => $transaction['data']['amount']['number'],
                'refundDestinationType' => \Craftgate\Model\RefundDestinationType::CARD
            );

            $response = json_decode($this->craftgateClient->payment()->refundPaymentTransaction($request), true);
            $response = $response['data'] ?? $response;
        } catch (\Exception $e) {
            $this->logger->log('error', $response['errors']['errorDescription']);
            throw new PaymentGatewayException($response['errors']['errorDescription']);
        }
        // Determine whether payment has been fully or partially refunded.
        if ($response['status'] != 'SUCCESS') {
            throw new PaymentGatewayException($response['errors']['errorDescription']);
        }
        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add(new Price(strval($response['refundPrice']), 'TRY'));
        if ($new_refunded_amount->lessThan($payment->getAmount())) {
            $payment->setState('partially_refunded');
        } else {
            $payment->setState('refunded');
        }

        $payment->setRefundedAmount($new_refunded_amount);
        $payment->save();
    }

  public function getRemoteCustomerId(UserInterface $account)
  {
    return parent::getRemoteCustomerId($account);
  }
}
