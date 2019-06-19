<?php
/**
 * Copyright (C) 2018  Daniel Pfeil <daniel.pfeil@itpfeil.de
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace DanielPfeil\Samlauthentication\Service;

use DanielPfeil\Samlauthentication\Enum\AuthenticationStatus;
use DanielPfeil\Samlauthentication\Utility\FactoryUtility;
use DanielPfeil\Samlauthentication\Utility\ServiceProviderUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

final class AuthenticationService extends \TYPO3\CMS\Sv\AuthenticationService
{
    final public function authUser(array $user): int
    {
        $serviceProviderUtility = ServiceProviderUtility::getInstance();

        $activeServiceProviders = $serviceProviderUtility->getActive(FactoryUtility::getServiceProviderModels());

        foreach ($activeServiceProviders as $activeServiceProvider) {
            $samlComponent = FactoryUtility::getSAMLUtility($activeServiceProvider);
            $samlUserData = $samlComponent->getUserData($activeServiceProvider);

            if (TYPO3_MODE === "FE") {
                $samlUsername = $samlUserData["fe_users"]["username"]->getValue();
            } else {
                $samlUsername = $samlUserData["be_users"]["username"]->getValue();
            }

            if ($samlUsername == $user["username"]) {
                return AuthenticationStatus::SUCCESS_BREAK;
            }
        }
        return AuthenticationStatus::FAIL_CONTINUE;
    }

    public function getUser()
    {
        //todo check if parent is needed before or after?
        $user = parent::getUser();
        if (!$user) {
            try {
                $serviceProviderUtility = ServiceProviderUtility::getInstance();
                $activeServiceProviders = $serviceProviderUtility->getActive(FactoryUtility::getServiceProviderModels());
                foreach ($activeServiceProviders as $activeServiceProvider) {
                    $samlComponent = FactoryUtility::getSAMLUtility($activeServiceProvider);
                    $storedSuccessfull = $samlComponent->saveUserData($activeServiceProvider);
                    if ($storedSuccessfull) {
                        break;
                    }
                }

                $user = parent::getUser();
            } catch (\Exception $exception) {
                //TODO implement
                DebuggerUtility::var_dump($exception);
            }
        }
        return $user;
    }
}
