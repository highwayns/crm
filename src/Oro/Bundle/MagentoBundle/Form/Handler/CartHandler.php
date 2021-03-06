<?php

namespace Oro\Bundle\MagentoBundle\Form\Handler;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\MagentoBundle\Entity\Cart;
use Oro\Bundle\MagentoBundle\Entity\CartItem;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var RegistryInterface */
    protected $manager;

    /**
     * @param FormInterface          $form
     * @param RequestStack           $requestStack
     * @param RegistryInterface      $registry
     * @param TokenAccessorInterface $security
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        RegistryInterface $registry,
        TokenAccessorInterface $security
    ) {
        $this->form = $form;
        $this->requestStack = $requestStack;
        $this->manager = $registry->getManager();
        $this->organization = $security->getOrganization();
    }

    /**
     * Process form
     *
     * @param  Cart $entity
     *
     * @return bool True on successful processing, false otherwise
     */
    public function process(Cart $entity)
    {
        $this->form->setData($entity);

        $request = $this->requestStack->getCurrentRequest();
        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->submit($request);

            if ($this->form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     *
     * @param Cart $entity
     */
    protected function onSuccess(Cart $entity)
    {
        $count = 0;
        /** @var CartItem $item */
        foreach ($entity->getCartItems() as $item) {
            $item->setCart($entity);
            ++$count;
        }

        $entity->setItemsCount($count);

        if (null === $entity->getOrganization()) {
            $entity->setOrganization($this->organization);
        }

        if ($entity->getShippingAddress() instanceof AbstractAddress
            && null === $entity->getShippingAddress()->getOrganization()
        ) {
            $entity->getShippingAddress()->setOrganization($this->organization);
        }

        if ($entity->getBillingAddress() instanceof AbstractAddress
            && null === $entity->getBillingAddress()->getOrganization()
        ) {
            $entity->getBillingAddress()->setOrganization($this->organization);
        }

        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
