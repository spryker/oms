<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication;

use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Oms\Communication\Builder\OmsTriggerFormCollectionBuilder;
use Spryker\Zed\Oms\Communication\Builder\OmsTriggerFormCollectionBuilderInterface;
use Spryker\Zed\Oms\Communication\Expander\OmsOrderDetailDataExpander;
use Spryker\Zed\Oms\Communication\Expander\OmsOrderDetailDataExpanderInterface;
use Spryker\Zed\Oms\Communication\Factory\OmsTriggerFormFactory;
use Spryker\Zed\Oms\Communication\Factory\OmsTriggerFormFactoryInterface;
use Spryker\Zed\Oms\Communication\Table\TransitionLogTable;
use Spryker\Zed\Oms\OmsDependencyProvider;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\OmsConfig getConfig()
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 * @method \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface getRepository()
 * @method \Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface getEntityManager()
 */
class OmsCommunicationFactory extends AbstractCommunicationFactory
{
    /**
     * @return \Spryker\Zed\Oms\Communication\Table\TransitionLogTable
     */
    public function createTransitionLogTable()
    {
        $queryContainer = $this->getQueryContainer();

        return new TransitionLogTable($queryContainer);
    }

    public function createOmsTriggerFormFactory(): OmsTriggerFormFactoryInterface
    {
        return new OmsTriggerFormFactory($this->getFormFactory());
    }

    public function createOmsTriggerFormCollectionBuilder(): OmsTriggerFormCollectionBuilderInterface
    {
        return new OmsTriggerFormCollectionBuilder($this->createOmsTriggerFormFactory());
    }

    public function createOmsOrderDetailDataExpander(): OmsOrderDetailDataExpanderInterface
    {
        return new OmsOrderDetailDataExpander($this->createOmsTriggerFormCollectionBuilder());
    }

    public function getCsrfTokenManager(): CsrfTokenManagerInterface
    {
        return $this->getProvidedDependency(OmsDependencyProvider::SERVICE_FORM_CSRF_PROVIDER);
    }
}
