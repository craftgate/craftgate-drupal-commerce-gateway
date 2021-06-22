<?php

namespace Drupal\commerce_craftgate\PluginForm\Craftgate;

use Drupal\commerce_payment\PluginForm\PaymentRefundForm as BasePaymentRefundForm;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

class PaymentRefundForm extends BasePaymentRefundForm
{
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);
        $form['amount']['#title'] = 'Toplam Miktar';
        $form['amount']['#disabled'] = true;
        /** @var \Drupal\commerce_payment_bdb\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();
        $craftgateClient = $paymentGatewayPlugin->craftgateClient;
        $remotePayment = json_decode($craftgateClient->payment()->retrievePayment($payment->getRemoteId()), true)['data'] ?? null;

        $form['transactions'] = array(
            '#type' => 'fieldset',
            '#title' => 'Kalem Kırılımları',
            '#attributes' => array(
                'class' => array('container-inline'),
            )
        );

        foreach ($remotePayment['paymentTransactions'] as $transaction) {
            $transactionElement = array(
                '#type' => 'fieldset',
                '#title' => '',
            );
            $transactionElement['refund'] = array(
                '#type' => 'checkbox',
                '#title' => 'İade Yap',
                '#default_value' => false,
            );
            $transactionElement['amount'] = [
                '#type' => 'commerce_price',
                '#title' => $transaction['externalId'] . ' - ' . $transaction['name'],
                '#default_value' => ['number' => $transaction['paidPrice'], 'currency_code' => $payment->getAmount()->getCurrencyCode()],
                '#available_currencies' => [$payment->getAmount()->getCurrencyCode()],
                '#states' => [
                    'enabled' => [
                        ':input[name="payment[transactions][' . $transaction['id'] . '][refund]"]' => ['checked' => true],
                    ]
                ]
            ];
            $form['transactions'][$transaction['id']] = $transactionElement;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValue($form['#parents']);
        $transactionCount = 0;
        foreach ($values['transactions'] as $transaction) {
            if ($transaction['refund'] ?? false) {
                $transactionCount += 1;
            }
        }
        if ($transactionCount != 1) {
            $form_state->setError($form['transactions'], '1 adet kalem seçmeniz gerekmektedir.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
    {
        $values = $form_state->getValue($form['#parents']);
        /** @var \Drupal\commerce_payment_bdb\Entity\PaymentInterface $payment */
        $payment = $this->entity;
        $selectedTransaction = null;
        foreach ($values['transactions'] as $id => $transaction) {
            if ($transaction['refund'] ?? false) {
                $selectedTransaction = ['id' => $id, 'data' => $transaction];
            }
        }
        /** @var \Drupal\commerce_payment_bdb\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $this->plugin;
        $payment_gateway_plugin->refundPaymentByTransaction($payment, $selectedTransaction);
    }
}
