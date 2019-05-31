<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Form;

use Spryker\Service\UtilText\Model\Url\Url;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @method \Spryker\Zed\Oms\OmsConfig getConfig()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface getRepository()
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 */
class OmsTriggerForm extends AbstractType
{
    public const OPTION_OMS_ACTION = 'OPTION_OMS_ACTION';
    public const OPTION_QUERY_PARAMS = 'OPTION_QUERY_PARAMS';
    public const OPTION_EVENT = 'OPTION_EVENT';
    public const OPTION_SUBMIT_BUTTON_CLASS = 'OPTION_SUBMIT_BUTTON_CLASS';

    public const FIELD_SUBMIT = 'submit';

    protected const ROUTE_OMS_TRIGGER = '/oms/trigger/';

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addSubmitField($builder, $options)
            ->setAction($builder, $options);
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            static::OPTION_EVENT => '',
            static::OPTION_OMS_ACTION => '',
            static::OPTION_QUERY_PARAMS => [],
            static::OPTION_SUBMIT_BUTTON_CLASS => '',
        ]);
    }

    /**
     * @return string
     */
    public function getParent(): string
    {
        return FormType::class;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     *
     * @return $this
     */
    protected function addSubmitField(FormBuilderInterface $builder, array $options)
    {
        $fieldOptions = [
            'label' => $options[static::OPTION_EVENT],
        ];

        if ($options[static::OPTION_SUBMIT_BUTTON_CLASS]) {
            $fieldOptions['attr'] = [
                'class' => $options[static::OPTION_SUBMIT_BUTTON_CLASS],
            ];
        }

        $builder->add(static::FIELD_SUBMIT, SubmitType::class, $fieldOptions);

        return $this;
    }

    /**
     * @param \Symfony\Component\Form\FormBuilderInterface $builder
     * @param array $options
     *
     * @return $this
     */
    protected function setAction(FormBuilderInterface $builder, array $options)
    {
        $builder->setAction(
            $this->createAction($options)
        );

        return $this;
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function createAction(array $options): string
    {
        return Url::generate(
            static::ROUTE_OMS_TRIGGER . $options[static::OPTION_OMS_ACTION],
            $options[static::OPTION_QUERY_PARAMS]
        );
    }
}
