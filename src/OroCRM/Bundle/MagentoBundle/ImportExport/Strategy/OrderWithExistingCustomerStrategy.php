<?php

namespace OroCRM\Bundle\MagentoBundle\ImportExport\Strategy;

use OroCRM\Bundle\MagentoBundle\Entity\Cart;
use OroCRM\Bundle\MagentoBundle\Entity\Customer;
use OroCRM\Bundle\MagentoBundle\Entity\Order;
use OroCRM\Bundle\MagentoBundle\Provider\Reader\ContextCartReader;
use OroCRM\Bundle\MagentoBundle\Provider\Reader\ContextCustomerReader;

class OrderWithExistingCustomerStrategy extends OrderStrategy
{
    const CONTEXT_ORDER_POST_PROCESS = 'postProcessOrders';

    /**
     * @var Customer|null
     */
    protected $customer;

    /**
     * @var Cart|null
     */
    protected $cart;

    /**
     * {@inheritdoc}
     */
    public function process($importingOrder)
    {
        $this->customer = null;
        $this->cart = null;

        if (!$this->isProcessingAllowed($importingOrder)) {
            $this->appendDataToContext(self::CONTEXT_ORDER_POST_PROCESS, $this->context->getValue('itemData'));

            return null;
        }

        return parent::process($importingOrder);
    }

    /**
     * @param Order $order
     * @return bool
     */
    protected function isProcessingAllowed(Order $order)
    {
        $this->customer = $this->findExistingEntity($order->getCustomer());
        $this->cart = $this->findExistingEntity($order->getCart());
        $isProcessingAllowed = true;

        $cartOriginId = $order->getCart()->getOriginId();
        if (!$this->cart && $cartOriginId) {
            $this->appendDataToContext(ContextCartReader::CONTEXT_POST_PROCESS_CARTS, $cartOriginId);

            $isProcessingAllowed = false;
        }

        $customerOriginId = $order->getCustomer()->getOriginId();
        if (!$this->customer && $customerOriginId) {
            $this->appendDataToContext(ContextCustomerReader::CONTEXT_POST_PROCESS_CUSTOMERS, $customerOriginId);

            $isProcessingAllowed = false;
        }

        return $isProcessingAllowed;
    }
}
