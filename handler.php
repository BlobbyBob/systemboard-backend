<?php
/*
 *  systemboard
 *  Copyright (C) 2020 Ben Swierzy
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Systemboard\Services\BoulderService;
use Systemboard\Services\DefaultService;
use Systemboard\Services\EditorService;
use Systemboard\Services\HoldService;
use Systemboard\Services\AccountService;
use Systemboard\Services\StatsService;
use Systemboard\Services\UserService;
use Systemboard\Services\WallService;

$getWallHandler = function (Request $request, Response $response, $args) {

    if ($this->has('wallService')) {
        /** @var WallService $wallService */
        $wallService = $this->get('wallService');
        return $wallService->get($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getHoldsHandler = function (Request $request, Response $response, $args) {

    if ($this->has('holdService')) {
        /** @var HoldService $holdService */
        $holdService = $this->get('holdService');
        return $holdService->getHolds($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getRankingHandler = function (Request $request, Response $response, $args) {

    if ($this->has('userService')) {
        /** @var UserService $userService */
        $userService = $this->get('userService');
        return $userService->getRanking($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getLoginHandler = function (Request $request, Response $response, $args) {

    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->login($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postRegistrationHandler = function (Request $request, Response $response, $args) {

    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->register($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postActivationHandler = function (Request $request, Response $response, $args) {

    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->activate($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postPwResetHandler = function (Request $request, Response $response, $args) {

    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->pwReset($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postPwResetMisuseHandler = function (Request $request, Response $response, $args) {

    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->pwResetMisuse($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putPwResetHandler = function (Request $request, Response $response, $args) {


    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->newPassword($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getLogoutHandler = function (Request $request, Response $response, $args) {

    if ($this->has('accountService')) {
        /** @var AccountService $accountService */
        $accountService = $this->get('accountService');
        return $accountService->logout($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getBoulderByIdHandler = function (Request $request, Response $response, $args) {

    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->getById($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getBoulderOfTheDayHandler = function (Request $request, Response $response, $args) {


    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->getBoulderOfTheDay($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postBoulderSearchHandler = function (Request $request, Response $response, $args) {


    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->search($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postBoulderHandler = function (Request $request, Response $response, $args) {

    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->post($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putBoulderHandler = function (Request $request, Response $response, $args) {

    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->put($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putBoulderClimbedHandler = function (Request $request, Response $response, $args) {

    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->putClimbed($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putBoulderVoteHandler = function (Request $request, Response $response, $args) {

    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->putVote($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putBoulderOfTheDayHandler = function (Request $request, Response $response, $args) {

    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->putBoulderOfTheDay($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$deleteBoulderHandler = function (Request $request, Response $response, $args) {


    if ($this->has('boulderService')) {
        /** @var BoulderService $boulderService */
        $boulderService = $this->get('boulderService');
        return $boulderService->delete($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getUserPrivateHandler = function (Request $request, Response $response, $args) {

    if ($this->has('userService')) {
        /** @var UserService $userService */
        $userService = $this->get('userService');
        return $userService->getPrivate($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getUserPublicHandler = function (Request $request, Response $response, $args) {

    if ($this->has('userService')) {
        /** @var UserService $userService */
        $userService = $this->get('userService');
        return $userService->getPublic($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putUserHandler = function (Request $request, Response $response, $args) {

    if ($this->has('userService')) {
        /** @var UserService $userService */
        $userService = $this->get('userService');
        return $userService->put($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getStatsHandler = function (Request $request, Response $response, $args) {

    if ($this->has('statsService')) {
        /** @var StatsService $statsService */
        $statsService = $this->get('statsService');
        return $statsService->get($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$getHoldHandler = function (Request $request, Response $response, $args) {

    if ($this->has('editorService')) {
        /** @var EditorService $editorService */
        $editorService = $this->get('editorService');
        return $editorService->getHold($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$postHoldHandler = function (Request $request, Response $response, $args) {

    if ($this->has('editorService')) {
        /** @var EditorService $editorService */
        $editorService = $this->get('editorService');
        return $editorService->postHold($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$putHoldHandler = function (Request $request, Response $response, $args) {

    if ($this->has('editorService')) {
        /** @var EditorService $editorService */
        $editorService = $this->get('editorService');
        return $editorService->putHold($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};

$deleteHoldHandler = function (Request $request, Response $response, $args) {

    if ($this->has('editorService')) {
        /** @var EditorService $editorService */
        $editorService = $this->get('editorService');
        return $editorService->deleteHold($request, $response, $args);
    }

    return DefaultService::notImplemented($request, $response);
};
