<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Controller;

use Spryker\Zed\Application\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Zed\Oms\Business\OmsFacade getFacade()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 */
class LogController extends AbstractController
{

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        $transitionLogTable = $this->getFactory()->createTransitionLogTable();

        return $this->viewResponse(['transitionLogTable' => $transitionLogTable->render()]);
    }

        /**
         * @param \Symfony\Component\HttpFoundation\Request $request
         *
         * @return mixed
         */
    public function tableAjaxAction(Request $request)
    {
        $transitionLogTable = $this->getFactory()->createTransitionLogTable();

        return $this->jsonResponse($transitionLogTable->fetchData());
    }

}
