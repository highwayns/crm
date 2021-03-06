<?php

namespace Oro\Bundle\SalesBundle\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\SalesBundle\Entity\Lead;
use Oro\Bundle\SalesBundle\Entity\LeadEmail;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class LeadEmailHandler
{
    /** @var FormFactory */
    protected $form;

    /** @var RequestStack */
    protected $requestStack;

    /** @var EntityManagerInterface */
    protected $manager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param FormFactory $form
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $manager
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        FormFactory $form,
        RequestStack $requestStack,
        EntityManagerInterface $manager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->form    = $form;
        $this->requestStack = $requestStack;
        $this->manager = $manager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Process form
     *
     * @param LeadEmail $entity
     *
     * @return bool True on successful processing, false otherwise
     *
     * @throws AccessDeniedException
     */
    public function process(LeadEmail $entity)
    {
        $form = $this->form->create('oro_sales_lead_email', $entity);

        $request = $this->requestStack->getCurrentRequest();
        $submitData = [
            'email' => $request->request->get('email'),
            'primary' => $request->request->get('primary')
        ];

        if (in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $form->submit($submitData);

            if ($form->isValid() && $request->request->get('entityId')) {
                /** @var Lead $lead */
                $lead = $this->manager->find(
                    'OroSalesBundle:Lead',
                    $request->request->get('entityId')
                );
                if (!$this->authorizationChecker->isGranted('EDIT', $lead)) {
                    throw new AccessDeniedException();
                }

                if ($lead->getPrimaryEmail() && $request->request->get('primary') === true) {
                    return false;
                }

                $this->onSuccess($entity, $lead);

                return true;
            }
        }

        return false;
    }

    /**
     * @param $id
     * @param ApiEntityManager $manager
     * @throws \Exception
     */
    public function handleDelete($id, ApiEntityManager $manager)
    {
        /** @var LeadEmail $leadEmail */
        $leadEmail = $manager->find($id);
        if (!$this->authorizationChecker->isGranted('EDIT', $leadEmail->getOwner())) {
            throw new AccessDeniedException();
        }

        if ($leadEmail->isPrimary() && $leadEmail->getOwner()->getEmails()->count() === 1) {
            $em = $manager->getObjectManager();
            $em->remove($leadEmail);
            $em->flush();
        } else {
            throw new \Exception("oro.sales.email.error.delete.more_one", 500);
        }
    }

    /**
     * @param LeadEmail $entity
     * @param Lead $lead
     */
    protected function onSuccess(LeadEmail $entity, Lead $lead)
    {
        $entity->setOwner($lead);
        $this->manager->persist($entity);
        $this->manager->flush();
    }
}
