<?php

namespace Oro\Bundle\AnalyticsBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\MagentoBundle\Entity\Order;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class LoadOrderData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var array Orders
     */
    protected $orderData = [
        [
            'status' => 'canceled',
            'incrementId' => '1',
            'customerEmail' => 'customer@email.com',
            'createdSub' => 'P1D',
            'updatedSub' => 'P1D',
            'discountAmount' => 4.40,
            'taxAmount' => 12.47,
            'shippingAmount' => 5,
            'totalPaidAmount' => 17.85,
            'subtotalAmount' => 17.85,
            'totalInvoicedAmount' => 11,
            'totalRefundedAmount' => 4,
            'totalCanceledAmount' => 0,
            'shippingMethod' => 'some unique shipping method',
            'remoteIp' => 'unique ip',
            'giftMessage' => 'some very unique gift message',
            'dataChannel' => 'Channel.CustomerChannel',
            'customer' => 'Channel.CustomerIdentity.CustomerIdentity',

            'reference' => 'order_1'
        ],
        [
            'status' => 'done',
            'incrementId' => '2',
            'customerEmail' => 'customer@email.com',
            'createdSub' => 'P6D',
            'updatedSub' => 'P6D',
            'discountAmount' => 4.40,
            'taxAmount' => 12.47,
            'shippingAmount' => 5,
            'totalPaidAmount' => 17.85,
            'subtotalAmount' => 15.5,
            'totalInvoicedAmount' => 11,
            'totalRefundedAmount' => 4,
            'totalCanceledAmount' => 0,
            'shippingMethod' => 'some unique shipping method',
            'remoteIp' => 'unique ip',
            'giftMessage' => 'some very unique gift message',
            'dataChannel' => 'Channel.CustomerChannel',
            'customer' => 'Channel.CustomerIdentity.CustomerIdentity',

            'reference' => 'order_2'
        ],
        [
            'status' => 'done',
            'incrementId' => '3',
            'customerEmail' => 'customer@email.com',
            'createdSub' => 'P1D',
            'updatedSub' => 'P1D',
            'discountAmount' => 4.40,
            'taxAmount' => 12.47,
            'shippingAmount' => 5,
            'totalPaidAmount' => 6.85,
            'subtotalAmount' => 15.5,
            'totalInvoicedAmount' => 11,
            'totalRefundedAmount' => 4,
            'totalCanceledAmount' => 0,
            'shippingMethod' => 'some unique shipping method',
            'remoteIp' => 'unique ip',
            'giftMessage' => 'some very unique gift message',
            'dataChannel' => 'Channel.CustomerChannel',
            'customer' => 'Channel.CustomerChannel.Customer',

            'reference' => 'order_3'
        ],
        [
            'status' => 'done',
            'incrementId' => '4',
            'customerEmail' => 'customer@email.com',
            'createdSub' => 'P364D',
            'updatedSub' => 'P364D',
            'discountAmount' => 4.40,
            'taxAmount' => 12.47,
            'shippingAmount' => 5,
            'totalPaidAmount' => 17.85,
            'subtotalAmount' => 5.85,
            'totalInvoicedAmount' => 11,
            'totalRefundedAmount' => 4,
            'totalCanceledAmount' => 0,
            'shippingMethod' => 'some unique shipping method',
            'remoteIp' => 'unique ip',
            'giftMessage' => 'some very unique gift message',
            'dataChannel' => 'Channel.CustomerChannel',
            'customer' => 'Channel.CustomerChannel.Customer',

            'reference' => 'order_4'
        ],
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param object $entity
     * @param array $data
     * @param array $excludeProperties
     */
    public function setEntityPropertyValues($entity, array $data, array $excludeProperties = [])
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            if (in_array($property, $excludeProperties)) {
                continue;
            }
            $propertyAccessor->setValue($entity, $property, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        /** @var User $admin */
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        foreach ($this->orderData as $data) {
            $entity = new Order();
            $entity->setOwner($admin);
            $entity->setOrganization($organization);
            $created = new \DateTime('now', new \DateTimeZone('UTC'));
            $entity->setCreatedAt($created->sub(new \DateInterval($data['createdSub'])));
            $updated = new \DateTime('now', new \DateTimeZone('UTC'));
            $entity->setUpdatedAt($updated->sub(new \DateInterval($data['updatedSub'])));

            $data['dataChannel'] = $this->getReference($data['dataChannel']);
            $data['customer'] = $this->getReference($data['customer']);

            $this->setEntityPropertyValues($entity, $data, ['reference', 'createdSub', 'updatedSub']);
            $this->setReference($data['reference'], $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadCustomerData::class];
    }
}
