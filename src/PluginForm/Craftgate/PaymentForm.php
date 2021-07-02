<?php

namespace Drupal\commerce_craftgate\PluginForm\Craftgate;

use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;

class PaymentForm extends BasePaymentOffsiteForm
{
    /**
     * {@inheritdoc}
     */
    public function buildConfigurationForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildConfigurationForm($form, $form_state);

        /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
        $payment = $this->entity;

        /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
        $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
        $configuration = $payment_gateway_plugin->getConfiguration();

        $order = $payment->getOrder();

        $craftgate = $payment_gateway_plugin->craftgateClient;

        $totalPrice = $order->getTotalPrice();
        $request = array(
            'price' => number_format($totalPrice->getNumber(), 2),
            'paidPrice' => number_format($totalPrice->getNumber(), 2),
            'paymentGroup' => \Craftgate\Model\PaymentGroup::LISTING_OR_SUBSCRIPTION,
            'currency' => $totalPrice->getCurrencyCode(),
            'conversationId' => $order->id(),
            'callbackUrl' => $form['#return_url'],
            'items' => []
        );
        $cardUserKey = $payment_gateway_plugin->getRemoteCustomerId($order->getCustomer());
        if ($cardUserKey ?? false) {
            $request['cardUserKey'] = $cardUserKey;
        }
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getAdjustedTotalPrice()->getNumber() == 0) {
                continue;
            }
            $item = [
                'name' => $orderItem->getPurchasedEntity()->label(),
                'price' => number_format($orderItem->getAdjustedTotalPrice()->getNumber(), 2),
                'externalId' => $orderItem->id()
            ];
            $request['items'][] = $item;
        }

        $response = json_decode($craftgate->payment()->initCheckoutPayment($request));

        if ($response->data ?? false) {
            $redirectUrl = $response->data->pageUrl;
        } else {
            throw new PaymentGatewayException($response->errors->errorDescription);
        }

        return $this->buildRedirectForm($form, $form_state, $redirectUrl, array());
    }
}
